<?php

namespace App\Jobs;

use App\Imports\PlainDataImport;
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

    public $timeout = 600;

    public $tries = 5;

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
        try {
            $company = Company::findOrFail($this->company);
            $user = User::findOrFail($this->user);

            if ($this->progressId) {
                JobProgress::updateOrCreate(['id' => $this->progressId], [
                    'type' => 'CDFIS',
                    'total_rows' => 0,
                    'processed_rows' => 0,
                    'progress_percentage' => 0,
                    'status' => 'processing',
                    'message' => 'Preparando importación...',
                ]);
            }

            // Obtener total filas
            $totalRows = count(Excel::toArray(new PlainDataImport($this->year, $company), $this->file)[0] ?? []);
            if ($this->progressId) {
                JobProgress::where('id', $this->progressId)->update(['total_rows' => $totalRows]);
            }

            // Importar
            $import = new PlainDataImport($this->year, $company, $this->progressId);
            $import->setTotalRows($totalRows);
            Excel::import($import, $this->file);

            // Finalizar buffer y progreso
            $import->finalize();

            JobProgress::where('id', $this->progressId)->update([
                'status' => 'completed',
                'progress_percentage' => 100,
                'message' => 'Importación completada exitosamente',
            ]);

        } catch (\Throwable $e) {
            Log::error('ProcessCFDI failed', ['error' => $e->getMessage()]);
            if ($this->progressId) {
                JobProgress::where('id', $this->progressId)->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);
            }
            throw $e;
        }
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
                'message' => 'Preparando importación de CFDIs...',
                'metadata' => [
                    'empleados_procesados' => 0,
                    'total_empleados' => 0,
                    'errores' => [],
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'year' => $this->year,
                    'uuid' => $this->uuid,
                    'tipo' => 'CFDI',
                ],
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
                'errores' => [],
                'company_id' => $this->company,
                'year' => $this->year,
                'uuid' => $this->uuid,
                'tipo' => 'CFDI',
            ],
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
            'file' => $this->filePath,
            'error' => $e->getMessage(),
        ]);

        if ($this->progressId) {
            $this->markProgressAsFailed('Error fatal en el proceso: '.$e->getMessage());
        }

        // Podrías notificar aquí también, o re-enfiletar una alerta
        // SendAdminAlert::dispatch(...);
    }
}
