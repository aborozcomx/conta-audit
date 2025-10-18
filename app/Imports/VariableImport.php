<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeQuota;
use App\Models\JobProgress;
use App\Models\Uma;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use PDO;
use Throwable;

class VariableImport implements OnEachRow, WithHeadingRow // , SkipsOnError, SkipsOnFailure
{
    // use SkipsFailures;
    /**
     * @param  Collection  $collection
     */
    public $year;

    protected $progressId;

    protected $totalRows = 0;

    protected $processedRows = 0;

    protected $errors = [];

    public function __construct($year, $progressId = null)
    {
        $this->year = $year;
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

        if ($row['sdi']) {
            $year = $this->year;
            $yearUma = ((int) $row['periodo'] === 1) ? $year - 1 : $year;

            $uma = Uma::where('year', $yearUma)->first();

            $employee = Employee::where('social_number', $row['nss'])->first();
            $employee = Employee::with([
                'company',
                'employee_salaries' => fn ($q) => $q->where('year', $year)->where('period', $row['periodo'])->orderBy('created_at', 'desc'),
            ])->where('social_number', $row['nss'])->first();

            if ($employee) {

                // $salary = $employee->employee_salaries->where('year', $year)->where('period', $row['periodo'])->first();
                $salary = $employee->employee_salaries->first();

                if ($salary) {
                    $sdi_quoted = $row['sdi'];
                    $sdi_aud = $salary->sdi_aud;
                    $difference = round($sdi_aud - $sdi_quoted, 2);

                    $salary->update([
                        'sdi_quoted' => $sdi_quoted,
                        'difference' => $difference,
                    ]);

                    $days = (int) $row['dias'];
                    $absence = (int) ($row['aus'] ?? 0);
                    $incapacity = (int) ($row['inc'] ?? 0);

                    $base_salary = $sdi_aud;
                    $total_days = $days - $absence - $incapacity;
                    $difference_days = $days - $total_days;
                    $days_incapacity = $days - $incapacity;

                    $salary_incapacity = round($base_salary * $days_incapacity, 2);
                    $salary_uma_bonus = round($uma->balance * $employee->company->vacation_bonus * $days_incapacity, 2);

                    $base_price_em = round($base_salary * $total_days, 2);
                    $base_price_rt = $base_price_em;
                    $base_price_iv = min($salary_incapacity, $salary_uma_bonus);

                    $salary_uma = round($uma->balance * $days_incapacity, 2);
                    $fixed_price = round($salary_uma * .2040, 2);
                    $sdmg = $base_salary > $uma->balance * 3
                        ? round(($base_price_em - $uma->balance * 3 * $total_days) * .015, 2)
                        : 0;
                    $in_cash = round($base_price_em * .0095, 2);
                    $disability_health = round($base_price_iv * .02375, 2);
                    $pensioners = round($base_price_em * .01425, 2);
                    $risk = $row['prima'] / 100;
                    $risk_price = round($base_price_rt * $risk, 2);
                    $nurseries = round($base_price_rt * .01, 2);

                    $total_audit = $fixed_price + $sdmg + $in_cash + $disability_health + $pensioners + $risk_price + $nurseries;
                    $subtotal = (float) $row['cuota_imss'];

                    $batch = EmployeeQuota::create([
                        'period' => $row['periodo'],
                        'year' => $year,
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'base_salary' => $base_salary,
                        'days' => $days,
                        'absence' => $absence,
                        'incapacity' => $incapacity,
                        'total_days' => $total_days,
                        'difference_days' => $difference_days,
                        'base_price_em' => $base_price_em,
                        'base_price_rt' => $base_price_rt,
                        'base_price_iv' => $base_price_iv,
                        'fixed_price' => $fixed_price,
                        'sdmg' => $sdmg,
                        'in_cash' => $in_cash,
                        'disability_health' => $disability_health,
                        'pensioners' => $pensioners,
                        'risk_price' => $risk_price,
                        'nurseries' => $nurseries,
                        'total_audit' => $total_audit,
                        'total_company' => $subtotal,
                        'difference' => round($total_audit - $subtotal, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

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
                'type' => 'Cuotas IMSS',
                'total_rows' => $this->totalRows,
                'processed_rows' => 0,
                'progress_percentage' => 0,
                'status' => JobProgress::STATUS_PROCESSING,
                'message' => 'Iniciando importación de datos...',
                'metadata' => [
                    'empleados_procesados' => 0,
                    'total_empleados' => $this->totalRows,
                    'errores' => [],
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
                    'message' => "Procesando Cuotas IMSS ({$this->processedRows}/{$this->totalRows})",
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

    public function __destruct()
    {
        // Solo marcar como completado si tenemos un progressId y no hubo errores fatales
        if ($this->progressId && $this->totalRows > 0) {
            Log::debug('Destructor called - attempting to mark as completed');
            $this->markAsCompleted();
        }
    }
    // public function onError(Throwable $e)
    // {
    //     // Log general errors during import (e.g., file reading issues)
    //     Log::error('Laravel Excel Import Error: ' . $e->getMessage());
    // }

    // /**
    //  * @param Failure[] $failures
    //  */
    // public function onFailure(Failure ...$failures)
    // {
    //     foreach ($failures as $failure) {
    //         // Get the row number where the failure occurred
    //         $row = $failure->row();
    //         // Get the attribute that failed validation (e.g., 'email', 'name')
    //         $attribute = $failure->attribute();
    //         // Get the error messages for the failed attribute
    //         $errors = implode(', ', $failure->errors());
    //         // Get the original row data
    //         $values = json_encode($failure->values());

    //         // Log the failure details
    //         Log::warning("Laravel Excel Import Failure on Row {$row}, Attribute '{$attribute}': {$errors}. Values: {$values}");
    //     }
    // }

    public function chunkSize(): int
    {
        return 500;
    }
}
