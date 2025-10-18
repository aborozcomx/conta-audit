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

    public $queue = 'cfdis';

    public function __construct($user, $file, $year, $message, $company, $uuid, $progressId = null)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->company = $company;
        $this->message = $message;
        $this->uuid = $uuid;
        $this->progressId = $progressId;

        Log::info('🎯 ProcessCFDI - JOB CONSTRUCTED', [
            'progress_id' => $progressId,
            'queue' => $this->queue,
        ]);

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
        Log::info('🚀 ProcessCFDI - JOB HANDLE STARTED', [
            'attempt' => $this->attempts(),
            'job_id' => $this->job->getJobId(),
        ]);

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

            // Ejecutar el import con el progressId
            $import = new PlainDataImport($this->year, $company, $this->progressId);
            $import->setTotalRows($totalRows);

            Excel::import(
                $import,
                $this->file
            );

            $import->markAsCompleted();

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
