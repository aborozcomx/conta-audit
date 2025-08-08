<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CompanyVariable;
use App\Models\Employee;

class ProcessCompanyVariables implements ShouldQueue
{
    use Queueable;

    private $file;
    private $year;


    public $timeout = 1200;
    public $tries = 25;
    /**
     * Create a new job instance.
     */
    public function __construct($file, $year)
    {
        $this->file = $file;
        $this->year = $year;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $import = new CompanyVariable($this->file);
        Excel::import($import, $this->file);

        $resultados = $import->getResultados();


        foreach($resultados as $employeeR) {
            $periods = [];

            if($employeeR['fecha'] == 12) {
                $periods = [1,2];

            }elseif($employeeR['fecha'] == 2) {
                $periods = [3,4];

            }elseif($employeeR['fecha'] == 4) {
                $periods = [5,6];

            }elseif($employeeR['fecha'] == 6) {
                $periods = [7,8];

            }elseif($employeeR['fecha'] == 8) {
                $periods = [9,10];

            }elseif($employeeR['fecha'] == 10) {
                $periods = [11,12];
            }

            $employee = Employee::where('number', $employeeR['numero_de_personal'])->first();


            if ($employee) {
                $days = $employeeR['suma_cantidad'];
                $import = $employeeR['suma_importe'];

                $salaries = $employee->employee_salaries->where('year', $this->year)->whereIn('period', $periods);

                if(count($salaries) > 0) {
                    foreach ($salaries as $salary) {

                        $sdi_variable = ($days <= 0 || $import <= 0) ? 0 : $import / $days;
                        $sdi_total = $salary->sdi +  $sdi_variable;
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
}
