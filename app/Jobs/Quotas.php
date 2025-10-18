<?php

namespace App\Jobs;

use App\Imports\VariableImport;
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

class Quotas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $user;

    private $file;

    private $year;

    private $message;

    private $company;

    private $uuid;

    private $progressId;

    public $timeout = 1200;

    public $tries = 12;

    public function __construct($user, $file, $year, $message, $company, $uuid, $progressId = null)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->message = $message;
        $this->company = $company;
        $this->uuid = $uuid;
        $this->progressId = $progressId;

    }

    public function tags(): array
    {
        return [
            'cuotas_imss',
            "company:{$this->company}",
            "year:{$this->year}",
            "uuid:{$this->uuid}",
            "user:{$this->user}",
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $company = Company::query()->findOrFail($this->company);
            $user2 = User::query()->findOrFail($this->user);
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
            $import = new VariableImport($this->year, $this->progressId);
            $import->setTotalRows($totalRows);

            Excel::import(
                $import,
                $this->file
            );

            $import->markAsCompleted();
        } catch (\Throwable $e) {
            // throw $th;
            Log::error('Cuotas IMSS failed in handle', [
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
            $totalRows = Excel::toArray(new VariableImport($this->year, $this->progressId), $this->file);

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
                'type' => 'Cuotas IMSS',
                'total_rows' => 0,
                'processed_rows' => 0,
                'progress_percentage' => 0,
                'status' => JobProgress::STATUS_PROCESSING,
                'message' => 'Preparando importación de Cuotas IMSS...',
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
        Log::error('Cuotas IMSS failed', [
            'company_id' => $this->company,
            'year' => $this->year,
            'uuid' => $this->uuid,
            'file' => $this->file,
            'error' => $e->getMessage(),
        ]);

        // Podrías notificar aquí también, o re-enfiletar una alerta
        // SendAdminAlert::dispatch(...);
    }
}
