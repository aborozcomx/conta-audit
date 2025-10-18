<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\JobProgress;
use App\Models\Uma;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PDO;

class PlainDataImport implements OnEachRow, WithHeadingRow
{
    protected $year;

    protected $company;

    protected $vacationCache = [];

    protected $progressId;

    protected $totalRows = 0;

    protected $processedRows = 0;

    protected $errors = [];

    public function __construct($year, $company, $progressId = null)
    {
        $this->year = $year;
        $this->company = $company;
        $this->progressId = $progressId;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        // Incrementar contador de filas procesadas
        $this->processedRows++;

        // Actualizar progreso cada 50 filas para no saturar la base de datos
        if ($this->progressId && $this->processedRows % 200 === 0) {
            $this->updateProgress();
        }

        $employee = Employee::firstOrCreate(
            ['social_number' => $row['numseguridadsocial']],
            [
                'name' => $row['nombrereceptor'],
                'clave' => $row['numempleado'],
                'puesto' => $row['pueston'] ?? 'N/A',
                'depto' => $row['departamento'] ?? 'N/A',
                'curp' => '',
                'age' => '',
                'rfc' => $row['rfc_receptor'],
                'start_date' => $row['fechainiciorellaboral'],
                'number' => $row['numempleado'],
                'social_number' => $row['numseguridadsocial'],
                'base_salary' => $row['salariobasecotapor'],
                'daily_salary' => $row['salariobasecotapor'],
                'company_id' => $this->company->id,
            ]
        );

        if ($row['tiponomina'] == 'O - Ordinaria' || $row['tiponomina'] == 'O') {
            $year = $this->year;
            $month = $this->getMonthFromPeriod($row['periodo']);

            $carbon = new Carbon("last day of $month $year");
            $start_date = new Carbon($employee->start_date);
            $age = $carbon->diff($start_date);

            $vacation = $this->getVacation($age->y);
            if (! $vacation) {
                Log::warning('Vacaciones no definidas', [
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'years' => $age->y,
                ]);
                // return;
            }

            $daily_bonus = round($row['salariobasecotapor'] * $this->company->vacation_days / 365, 2);
            $vacations_import = $row['salariobasecotapor'] * $vacation->days;
            $vacation_bonus = round($vacations_import * ($this->company->vacation_bonus / 100) / 365, 2);

            $yearUma = ((int) $row['periodo'] === 1) ? $year - 1 : $year;
            $sdi_tope = Uma::where('year', $yearUma)->first();
            $sdi_tope = $sdi_tope->balance * 25;

            $sdi = round($row['salariobasecotapor'] + $daily_bonus + $vacation_bonus, 2);

            $salary = EmployeeSalary::where('year', $year)
                ->where('period', $row['periodo'])
                ->where('employee_id', $employee->id)
                ->first();

            $salaryData = [
                'period' => $row['periodo'],
                'year' => $year,
                'employee_id' => $employee->id,
                'category_id' => 1,
                'daily_salary' => $row['salariobasecotapor'],
                'daily_bonus' => $daily_bonus,
                'vacations_days' => $vacation->days,
                'vacations_import' => $vacations_import,
                'vacation_bonus' => $vacation_bonus,
                'sdi' => $sdi,
                'total_sdi' => 0,
                'sdi_limit' => $sdi_tope,
                'company_id' => $this->company->id,
                'variables' => '{}',
            ];

            EmployeeSalary::create($salaryData);
        }

    }

    public function setTotalRows($totalRows)
    {
        $this->totalRows = $totalRows;
        if ($this->progressId) {
            $this->initializeProgress();
        }
    }

    protected function initializeProgress()
    {
        if (! $this->progressId) {
            return;
        }

        JobProgress::updateOrCreate(
            ['id' => $this->progressId],
            [
                'type' => 'CDFIS',
                'total_rows' => $this->totalRows,
                'processed_rows' => 0,
                'progress_percentage' => 0,
                'status' => JobProgress::STATUS_PROCESSING,
                'message' => 'Iniciando importación de datos...',
                'metadata' => [
                    'empleados_procesados' => 0,
                    'total_empleados' => $this->totalRows,
                    'errores' => [],
                    'company_id' => $this->company->id,
                    'year' => $this->year,
                ],
            ]
        );
    }

    protected function updateProgress()
    {
        if (! $this->progressId) {
            return;
        }

        $percentage = $this->totalRows > 0 ? round(($this->processedRows / $this->totalRows) * 100) : 0;

        Log::debug('=== FORCE COMMIT UPDATE WITH METADATA ===');
        Log::debug("ID: {$this->progressId}, Rows: {$this->processedRows}/{$this->totalRows}");

        try {
            // Desactivar autocommit temporalmente y forzar commit
            DB::connection()->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);

            // Primero obtener el metadata actual para no perderlo
            $currentProgress = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->first(['metadata']);

            $currentMetadata = [];
            if ($currentProgress && $currentProgress->metadata) {
                $currentMetadata = json_decode($currentProgress->metadata, true) ?? [];
            }

            Log::debug('Current metadata before update:', $currentMetadata);

            // Preparar el nuevo metadata
            $newMetadata = array_merge($currentMetadata, [
                'empleados_procesados' => $this->processedRows,
                'total_empleados' => $this->totalRows,
                'errores' => $this->errors,
                'error_count' => count($this->errors),
                'ultima_actualizacion' => now()->toDateTimeString(),
            ]);

            Log::debug('New metadata to save:', $newMetadata);

            // Actualizar incluyendo el campo metadata
            $result = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->update([
                    'processed_rows' => $this->processedRows,
                    'progress_percentage' => $percentage,
                    'message' => "Procesando CFDIS ({$this->processedRows}/{$this->totalRows})",
                    'metadata' => json_encode($newMetadata), // ← Aquí agregamos metadata
                    'updated_at' => now(),
                ]);

            // Forzar commit explícito
            DB::commit();

            Log::debug("FORCE COMMIT WITH METADATA - Rows affected: {$result}");

            // Verificar inmediatamente todos los campos
            $current = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->first();

            Log::debug("IMMEDIATE VERIFICATION - Processed: {$current->processed_rows}");

            // Verificación específica del metadata
            if ($current && $current->metadata) {
                $verifiedMetadata = json_decode($current->metadata, true);
                Log::debug('METADATA VERIFICATION:', $verifiedMetadata);

                // Verificar campos específicos del metadata
                $metaEmpleados = $verifiedMetadata['empleados_procesados'] ?? 'NOT_FOUND';
                $metaTotal = $verifiedMetadata['total_empleados'] ?? 'NOT_FOUND';
                Log::debug("Metadata check - Empleados: {$metaEmpleados}, Total: {$metaTotal}");
            } else {
                Log::warning('No metadata found after update');
            }

            // Reactivar autocommit
            DB::connection()->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        } catch (\Exception $e) {
            Log::error('Force commit with metadata error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollBack();
        }
    }

    public function markAsCompleted()
    {
        if (! $this->progressId) {
            return;
        }

        Log::debug('=== MARKING AS COMPLETED ===');
        Log::debug("ID: {$this->progressId}, Final rows: {$this->processedRows}/{$this->totalRows}");

        try {
            // Desactivar autocommit temporalmente
            DB::connection()->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);

            // Obtener metadata actual
            $currentProgress = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->first(['metadata']);

            $currentMetadata = [];
            if ($currentProgress && $currentProgress->metadata) {
                $currentMetadata = json_decode($currentProgress->metadata, true) ?? [];
            }

            // Preparar metadata final
            $finalMetadata = array_merge($currentMetadata, [
                'empleados_procesados' => $this->processedRows,
                'total_empleados' => $this->totalRows,
                'errores' => $this->errors,
                'error_count' => count($this->errors),
                'completado_en' => now()->toDateTimeString(),
                'estado_final' => 'completado',
            ]);

            Log::debug('Final metadata:', $finalMetadata);

            // Marcar como completado
            $result = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->update([
                    'status' => 'completed',
                    'progress_percentage' => 100,
                    'processed_rows' => $this->processedRows,
                    'message' => 'Importación completada exitosamente',
                    'metadata' => json_encode($finalMetadata),
                    'updated_at' => now(),
                ]);

            // Forzar commit
            DB::commit();

            Log::debug("MARK AS COMPLETED - Rows affected: {$result}");

            // Verificación
            $verified = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->first(['status', 'progress_percentage', 'message']);

            Log::debug("COMPLETED VERIFICATION - Status: {$verified->status}, Percentage: {$verified->progress_percentage}%");

            // Reactivar autocommit
            DB::connection()->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        } catch (\Exception $e) {
            Log::error('Error marking as completed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollBack();
        }
    }

    public function markAsFailed($errorMessage)
    {
        if (! $this->progressId) {
            return;
        }

        Log::debug('=== MARKING AS FAILED ===');
        Log::debug("ID: {$this->progressId}, Error: {$errorMessage}");

        try {
            DB::connection()->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);

            // Obtener metadata actual
            $currentProgress = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->first(['metadata']);

            $currentMetadata = [];
            if ($currentProgress && $currentProgress->metadata) {
                $currentMetadata = json_decode($currentProgress->metadata, true) ?? [];
            }

            // Preparar metadata de error
            $errorMetadata = array_merge($currentMetadata, [
                'empleados_procesados' => $this->processedRows,
                'total_empleados' => $this->totalRows,
                'errores' => $this->errors,
                'error_count' => count($this->errors),
                'error_final' => $errorMessage,
                'fallo_en' => now()->toDateTimeString(),
            ]);

            // Marcar como fallido
            $result = DB::table('job_progress')
                ->where('id', $this->progressId)
                ->update([
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'metadata' => json_encode($errorMetadata),
                    'updated_at' => now(),
                ]);

            DB::commit();
            Log::debug("MARK AS FAILED - Rows affected: {$result}");

            DB::connection()->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        } catch (\Exception $e) {
            Log::error('Error marking as failed', ['error' => $e->getMessage()]);
            DB::rollBack();
        }
    }

    protected function addError($error)
    {
        $this->errors[] = [
            'fila' => $this->processedRows,
            'error' => $error,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Limitar a los últimos 100 errores para no saturar
        if (count($this->errors) > 100) {
            $this->errors = array_slice($this->errors, -100);
        }
    }

    protected function getVacation($years)
    {
        if (! isset($this->vacationCache[$years])) {
            $this->vacationCache[$years] = Vacation::where('years', $years)->where('category_id', 1)->first();
        }

        return $this->vacationCache[$years];
    }

    protected function getMonthFromPeriod($period)
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return $months[$period] ?? 'January';
    }

    public function chunkSize(): int
    {
        return 200; // reduce si sigue consumiendo mucha memoria
    }

    public function __destruct()
    {
        // Solo marcar como completado si tenemos un progressId y no hubo errores fatales
        if ($this->progressId && $this->totalRows > 0) {
            Log::debug('Destructor called - attempting to mark as completed');
            $this->markAsCompleted();
        }
    }
}
