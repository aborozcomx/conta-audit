<?php

namespace App\Jobs;

use App\Imports\CompanyVariable;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessCompanyVariables implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $file;

    private $year;

    private $user;

    private $company;

    private $message;

    public $timeout = 1200;

    public $tries = 12;

    /**
     * Create a new job instance.
     */
    public function __construct($file, $year, $company)
    {
        $this->file = $file;
        $this->year = $year;
        $this->company = $company;
        // $this->message = $message;

    }

    public function tags(): array
    {
        return [
            'variables',
            "year:{$this->year}",
        ];
    }

    /** Backoff exponencial */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        // $user2 = User::query()->findOrFail($this->user);
        // Excel::queueImport(
        //     //new RawCfdiImport($this->year, $this->user->id, $this->company->id, $this->uuid),
        //     new CompanyVariableXDI($this->year),
        //     $this->file
        // )
        // ->onQueue('variables')
        // ->chain([
        //     (new SendCfdiNotification($user2, $this->message))->onQueue('notifications'),
        // ]);
        $import = new CompanyVariable($this->file);
        Excel::import($import, $this->file);

        $resultados = $import->getResultados();

        foreach ($resultados as $employeeR) {
            $periods = [];

            if ($employeeR['fecha'] == 12) {
                $periods = [1, 2];

            } elseif ($employeeR['fecha'] == 2) {
                $periods = [3, 4];

            } elseif ($employeeR['fecha'] == 4) {
                $periods = [5, 6];

            } elseif ($employeeR['fecha'] == 6) {
                $periods = [7, 8];

            } elseif ($employeeR['fecha'] == 8) {
                $periods = [9, 10];

            } elseif ($employeeR['fecha'] == 10) {
                $periods = [11, 12];
            }

            $employee = Employee::where('number', $employeeR['numero_de_personal'])->first();

            if ($employee) {
                $days = $employeeR['suma_cantidad'];
                $import = $employeeR['suma_importe'];

                $salaries = $employee->employee_salaries->where('year', $this->year)->whereIn('period', $periods);

                if (count($salaries) > 0) {
                    foreach ($salaries as $salary) {

                        $sdi_variable = ($days <= 0 || $import <= 0) ? 0 : $import / $days;
                        $sdi_total = $salary->sdi + $sdi_variable;
                        $sdi_aud = $sdi_total > $salary->sdi_limit ? $salary->sdi_limit : $sdi_total;

                        $salary->update([
                            'sdi_variable' => floatval(round($sdi_variable, 2)),
                            'total_sdi' => floatval(round($sdi_total, 2)),
                            'sdi_aud' => floatval(round($sdi_aud, 2)),
                        ]);
                    }
                }
            }
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessVariables failed', [
            'year' => $this->year,
            'file' => $this->file,
            'error' => $e->getMessage(),
        ]);

        // Podrías notificar aquí también, o re-enfiletar una alerta
        // SendAdminAlert::dispatch(...);
    }
}
