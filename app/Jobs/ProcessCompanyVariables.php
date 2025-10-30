<?php

namespace App\Jobs;

use App\Imports\CompanyVariable;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\JobProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessCompanyVariables implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $file;

    private $year;

    private $company;

    public $timeout = 600; // 1 hora

    public $tries = 5;

    private $periodMap = [
        1 => [3, 4],
        2 => [3, 4],
        3 => [5, 6],
        4 => [5, 6],
        5 => [7, 8],
        6 => [7, 8],
        7 => [9, 10],
        8 => [9, 10],
        9 => [11, 12],
        10 => [11, 12],
        11 => [1, 2],
        12 => [1, 2],
    ];

    private $jobProgressId;

    public function __construct($file, $year, $company, $jobProgressId = null)
    {
        $this->file = $file;
        $this->year = $year;
        $this->company = $company;
        $this->jobProgressId = $jobProgressId;
    }

    public function tags(): array
    {
        return ['variables', "year:{$this->year}"];
    }

    public function handle(): void
    {
        $this->updateProgress(0, 'Iniciando procesamiento de variables de la CIA...');

        Log::info('Iniciando ProcessCompanyVariables', [
            'file' => $this->file,
            'year' => $this->year,
            'company' => $this->company,
        ]);

        // Liberar memoria antes de empezar
        gc_collect_cycles();

        try {
            $import = new CompanyVariable;
            Excel::import($import, $this->file);
            $resultados = $import->getResultados();

            $this->updateProgress(30, 'Excel procesado, iniciando actualización de variables...', [
                'total_empleados' => $resultados->count(),
                'filas_procesadas' => $import->getProcessedRows(),
            ]);

            if ($resultados->isEmpty()) {
                Log::warning('No se encontraron resultados para procesar');
                $this->updateProgress(100, 'No se encontraron resultados para procesar', [], 'completed');

                return;
            }

            $this->processResultados($resultados);

            $this->updateProgress(100, 'Procesamiento completado exitosamente', [], 'completed');

            Log::info('ProcessCompanyVariables completado exitosamente');

        } catch (\Exception $e) {
            $this->updateProgress(0, 'Error en el procesamiento: '.$e->getMessage(), [
                'error_trace' => $e->getTraceAsString(),
            ], 'failed');

            Log::error('Error en ProcessCompanyVariables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            // Limpieza final de memoria
            unset($import, $resultados);
            gc_collect_cycles();
        }
    }

    private function processResultados($resultados): void
    {
        $totalEmpleados = $resultados->count();
        $chunkSize = 25; // Reducir chunk size para updates más frecuentes
        $chunks = $resultados->chunk($chunkSize); // Procesar en chunks de 100 empleados
        $totalChunks = $chunks->count();
        $currentChunk = 0;
        $empleadosProcesados = 0;

        foreach ($chunks as $chunkIndex => $chunk) {
            Log::debug("Procesando chunk {$chunkIndex}", ['size' => $chunk->count()]);
            $currentChunk++;
            $empleadosProcesados += $chunk->count();

            // Calcular progreso: 30% (Excel) + 70% (procesamiento)
            $progresoBase = 30;
            $progresoProcesamiento = ($empleadosProcesados / $totalEmpleados) * 70;
            $progresoTotal = (int) min($progresoBase + $progresoProcesamiento, 95);

            $this->updateProgress(
                $progresoTotal,
                "Procesando variables CIA ({$empleadosProcesados}/{$totalEmpleados})...",
                [
                    'total_empleados' => $totalEmpleados,
                    'empleados_procesados' => $empleadosProcesados,
                    'chunk_actual' => $currentChunk,
                    'total_chunks' => $totalChunks,
                    'progreso_actual' => $progresoTotal,
                ]
            );

            $this->processChunk($chunk);

            // Liberar memoria cada 5 chunks
            if ($chunkIndex % 5 === 0) {
                gc_collect_cycles();
            }
        }
    }

    private function processChunk($chunk): void
    {
        $employeeNumbers = $chunk->pluck('numero_de_personal')->toArray();

        // Cargar empleados con sus salarios
        $employees = Employee::whereIn('number', $employeeNumbers)
            ->with(['employee_salaries' => function ($query) {
                $query->where('year', $this->year)
                    ->select(['id', 'employee_id', 'year', 'period', 'sdi', 'sdi_limit']);
            }])
            ->get(['id', 'number'])
            ->keyBy('number');

        $updates = [];

        foreach ($chunk as $employeeR) {
            $employee = $employees[$employeeR['numero_de_personal']] ?? null;

            $periods = $this->periodMap[$employeeR['fecha']] ?? [];

            if ($employee) {
                Log::info('Employee', [
                    'period' => $employeeR['fecha'],
                    'employee_r' => $employeeR,
                    'periods' => $periods,
                    'employee' => $employee,
                ]);

                $days = $employeeR['suma_cantidad'];
                $import = $employeeR['suma_importe'];
                $sdi_variable = ($days <= 0 || $import <= 0) ? 0 : $import / $days;

                // Filtrar salarios por períodos
                $salaries = $employee->employee_salaries->whereIn('period', $periods);

                foreach ($salaries as $salary) {
                    $sdi_total = $salary->sdi + $sdi_variable;
                    $sdi_aud = $sdi_total > $salary->sdi_limit ? $salary->sdi_limit : $sdi_total;

                    $updates[] = [
                        'id' => $salary->id,
                        'sdi_variable' => round($sdi_variable, 2),
                        'total_sdi' => round($sdi_total, 2),
                        'sdi_aud' => round($sdi_aud, 2),
                    ];
                }
            }
        }

        if (! empty($updates)) {
            $this->batchUpdateSalaries($updates);
        }
    }

    private function batchUpdateSalaries(array $updates): void
    {
        $table = (new EmployeeSalary)->getTable();
        $chunkedUpdates = array_chunk($updates, 100); // Actualizar en lotes de 100

        foreach ($chunkedUpdates as $updateChunk) {
            $caseSdiVariable = $this->buildCaseSQL('sdi_variable', $updateChunk);
            $caseTotalSdi = $this->buildCaseSQL('total_sdi', $updateChunk);
            $caseSdiAud = $this->buildCaseSQL('sdi_aud', $updateChunk);

            $ids = implode(',', array_column($updateChunk, 'id'));

            DB::statement("
                UPDATE {$table}
                SET
                    sdi_variable = CASE id {$caseSdiVariable} ELSE sdi_variable END,
                    total_sdi = CASE id {$caseTotalSdi} ELSE total_sdi END,
                    sdi_aud = CASE id {$caseSdiAud} ELSE sdi_aud END
                WHERE id IN ({$ids})
            ");
        }
    }

    private function buildCaseSQL(string $field, array $updates): string
    {
        $cases = '';
        foreach ($updates as $update) {
            $cases .= "WHEN {$update['id']} THEN {$update[$field]} ";
        }

        return $cases;
    }

    private function updateProgress(int $percentage, string $message = '', array $metadata = [], string $status = 'processing'): void
    {
        if (! $this->jobProgressId) {
            return;
        }

        try {
            JobProgress::findOrFail($this->jobProgressId)->update([
                'progress_percentage' => $metadata['progreso_actual'] ?? $percentage,
                'processed_rows' => $metadata['empleados_procesados'] ?? $percentage,
                'message' => $message,
                'metadata' => $metadata,
                'status' => $status,
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating progress', [
                'job_progress_id' => $this->jobProgressId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCompanyVariables failed', [
            'year' => $this->year,
            'file' => $this->file,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
