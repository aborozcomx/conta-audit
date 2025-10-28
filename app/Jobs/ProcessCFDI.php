<?php

namespace App\Jobs;

use App\Imports\PlainDataImport;
use App\Imports\RawCfdiImport;
use App\Models\Company;
use App\Models\JobProgress;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessCFDI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $user;

    private $file;

    private $year;

    private $company;

    private $message;

    private $uuid;

    private $progressId;

    public $timeout = 1200;

    public $tries = 12;

    public function __construct($user, $file, $year, $message, $company, $uuid, $progressId = null)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->company = $company;
        $this->message = $message;
        $this->uuid = $uuid;
        $this->progressId = $progressId;

    }

    /**
     * Execute the job.
     */
    public function tags(): array
    {
        return [
            'cfdi',
            "company:{$this->company}",
            "year:{$this->year}",
            "uuid:{$this->uuid}",
            "user:{$this->user}",
        ];
    }

    public function handle(): void
    {
        // $data = Excel::import(new PlainDataImport($this->year), $this->file);

        // $company2 = Company::query()->findOrFail($this->company);
        // /** @var User $user */
        // $user2 = User::query()->findOrFail($this->user);

        // Excel::queueImport(
        //     // new RawCfdiImport($this->year, $this->user->id, $this->company->id, $this->uuid),
        //     new PlainDataImport($this->year, $company2, $this->uuid),
        //     $this->file
        // )
        //     ->onQueue('cfdis')
        //     ->chain([
        //         (new SendCfdiNotification($user2, $this->message))->onQueue('notifications'),
        //     ]);
        try {
            $company = Company::query()->findOrFail($this->company);
            $user = User::query()->findOrFail($this->user);

            // Inicializar progreso si existe
            if ($this->progressId) {
                $this->initializeProgress($company);
            }

            // Primero obtenemos el total de filas para el progreso
            $totalRows = $this->getTotalRows();

            if ($this->progressId) {
                $this->updateTotalRows($totalRows);
            }

            Log::info("🎯 Starting CHUNKED import of {$totalRows} rows");

            // CHUNK SIZE DINÁMICO - más pequeño para archivos grandes
            $chunkSize = $this->calculateChunkSize($totalRows);
            $totalChunks = ceil($totalRows / $chunkSize);

            Log::info('🔧 Chunk configuration', [
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
                'estimated_memory_per_chunk_mb' => round($chunkSize * 0.1, 2), // ~100KB por fila
            ]);

            for ($chunk = 1; $chunk <= $totalChunks; $chunk++) {
                $this->processChunk($chunk, $totalChunks, $chunkSize, $totalRows, $this->file, $company);
            }

            // Marcar como completado
            if ($this->progressId) {
                $this->markAsCompleted($totalRows);
            }

            Log::info('✅ CHUNKED IMPORT COMPLETED SUCCESSFULLY');
            // Ejecutar el import con el progressId
            // $import = new PlainDataImport($this->year, $company, $this->progressId);
            // $import->setTotalRows($totalRows);

            // Excel::import(
            //     $import,
            //     $this->file
            // );

            // $import->markAsCompleted();

        } catch (\Exception $e) {
            Log::error('ProcessCFDI failed in handle', [
                'company_id' => $this->company,
                'year' => $this->year,
                'uuid' => $this->uuid,
                'file' => $this->file,
                'error' => $e->getMessage(),
            ]);

            if ($this->progressId) {
                $this->markProgressAsFailed('Error al procesar el archivo: '.$e->getMessage());
            }

            throw $e;
        }
    }

    private function calculateChunkSize($totalRows): int
    {
        if ($totalRows > 20000) {
            return 500;
        }
        if ($totalRows > 10000) {
            return 1000;
        }
        if ($totalRows > 5000) {
            return 2000;
        }

        return 3000;
    }

    private function processChunk($chunk, $totalChunks, $chunkSize, $totalRows, $fullPath, $company)
    {
        $startRow = (($chunk - 1) * $chunkSize) + 2;

        Log::info("🔄 Processing chunk {$chunk}/{$totalChunks}", [
            'start_row' => $startRow,
            'chunk_size' => $chunkSize,
        ]);

        // Crear import SIN progressId - el Job maneja el progreso
        $import = new PlainDataImport($this->year, $company, null); // ← null aquí
        $import->setTotalRows($totalRows);
        $import->setChunkOffset($startRow);
        $import->setChunkSize($chunkSize);

        // Procesar el chunk
        Excel::import($import, $fullPath);

        // ACTUALIZAR PROGRESO GLOBAL basado en chunks completados
        $processedRows = min($chunk * $chunkSize, $totalRows);
        $percentage = round(($chunk / $totalChunks) * 100);

        if ($this->progressId) {
            $this->updateProgress($processedRows, $percentage, $chunk, $totalChunks);
        }

        // Limpiar memoria
        unset($import);
        gc_collect_cycles();

        Log::info("✅ Chunk {$chunk} completed - Progress: {$percentage}%");
    }

    private function getTotalRows(): int
    {
        try {
            $totalRows = Excel::toArray(new PlainDataImport($this->year, Company::find($this->company), $this->progressId), $this->file);

            return count($totalRows[0] ?? []);
        } catch (\Exception $e) {
            Log::warning('No se pudo obtener el total de filas', [
                'error' => $e->getMessage(),
                'file' => $this->file,
            ]);

            return 0;
        }
    }

    private function initializeProgress(Company $company): void
    {
        JobProgress::updateOrCreate(
            ['id' => $this->progressId],
            [
                'type' => 'CDFIS',
                'total_rows' => 0,
                'processed_rows' => 0,
                'progress_percentage' => 0,
                'status' => JobProgress::STATUS_PROCESSING,
                'message' => 'Preparando importación...',
                'metadata' => [
                    'empleados_procesados' => 0,
                    'total_empleados' => 0,
                    'company_id' => $company->id,
                    'year' => $this->year,
                ],
                'company_id' => $company->id,
                'user_id' => $this->user,
            ]
        );
    }

    private function updateTotalRows(int $totalRows): void
    {
        JobProgress::where('id', $this->progressId)->update([
            'total_rows' => $totalRows,
            'metadata' => [
                'empleados_procesados' => 0,
                'total_empleados' => $totalRows,
                'company_id' => $this->company,
            ],
        ]);
    }

    private function updateProgress(int $processedRows, int $percentage, int $currentChunk, int $totalChunks): void
    {
        JobProgress::where('id', $this->progressId)->update([
            'processed_rows' => $processedRows,
            'progress_percentage' => $percentage,
            'message' => "Procesando lote {$currentChunk}/{$totalChunks} ({$percentage}%)",
            'metadata' => [
                'empleados_procesados' => $processedRows,
                'total_empleados' => $this->getTotalRows(),
                'lote_actual' => $currentChunk,
                'total_lotes' => $totalChunks,
            ],
            'updated_at' => now(),
        ]);
    }

    private function markAsCompleted(int $totalRows): void
    {
        JobProgress::where('id', $this->progressId)->update([
            'status' => JobProgress::STATUS_COMPLETED,
            'progress_percentage' => 100,
            'message' => 'Importación completada exitosamente',
            'processed_rows' => $totalRows,
            'metadata' => [
                'empleados_procesados' => $totalRows,
                'total_empleados' => $totalRows,
                'completado_en' => now()->toDateTimeString(),
            ],
            'updated_at' => now(),
        ]);
    }

    private function markProgressAsFailed(string $errorMessage): void
    {
        JobProgress::where('id', $this->progressId)->update([
            'status' => JobProgress::STATUS_FAILED,
            'message' => $errorMessage,
            'updated_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCFDI failed', [
            'company_id' => $this->company,
            'year' => $this->year,
            'uuid' => $this->uuid,
            'file' => $this->file,
            'error' => $e->getMessage(),
        ]);

        if ($this->progressId) {
            $this->markProgressAsFailed('Error fatal en el proceso: '.$e->getMessage());
        }

        // Podrías notificar aquí también, o re-enfiletar una alerta
        // SendAdminAlert::dispatch(...);
    }
}
