<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CompanyVariable;
use App\Models\EmployeeSalary;

class CalculateDifference implements ShouldQueue
{
    use Queueable;

    private $company;
    private $year;
    private $period;


    public $timeout = 1200;
    /**
     * Create a new job instance.
     */
    public function __construct($company, $period, $year)
    {
        $this->company = $company;
        $this->period = $period;
        $this->year = $year;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $salaries = EmployeeSalary::with('employee')->where('year', $this->year)->where('period', $this->period)->whereRelation('employee', 'company_id', $this->company)->get();


        foreach($salaries as $salary) {
            $sdi_quoted = $salary->sdi_quoted;
            $sdi_aud = $salary->sdi_aud;
            $difference = round($sdi_aud - $sdi_quoted, 2);

            $salary->update([
                'sdi_quoted' => $sdi_quoted,
                'difference' => $difference,
            ]);
        }
    }
}
