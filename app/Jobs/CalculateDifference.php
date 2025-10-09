<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CompanyVariable;
use App\Models\EmployeeSalary;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CalculateDifference implements ShouldQueue
{
    use Queueable;

    private $company;
    private $year;
    private $period;


    public $timeout = 1200;
    public $tries = 10;
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
        // $salaries = EmployeeSalary::with('employee')
        //     ->where('year', $this->year)
        //     ->where('period', $this->period)
        //     ->where('total_sdi', 0)
        //     ->whereRelation('employee', 'company_id', $this->company)
        //     ->toRawSQL();

        $salaries = DB::table('employee_salaries')
            ->selectRaw('employee_salaries.*, employees.name, employees.rfc, employees.start_date,employees.social_number')
            ->join('employees', 'employees.id', '=', 'employee_salaries.employee_id')
            ->where('employee_salaries.period', $this->period)
            ->where('employee_salaries.year', $this->year)
            ->where('employee_salaries.company_id', $this->company)
            ->where('employee_salaries.total_sdi', 0)
            ->orderBy('employees.name')
            ->get();


        $companySalary = Company::find($this->company);

        foreach($salaries as $salary) {

            $salary2 = EmployeeSalary::find($salary->id);
            $daily_bonus = round($salary2->daily_salary * $companySalary->vacation_days / 365, 2);

            $sdi = round($salary2->daily_salary + $daily_bonus + $salary2->vacation_bonus, 2);

            $sdi_total = $sdi +  $salary2->sdi_variable;
            $sdi_aud = $sdi_total > $salary2->sdi_limit ? $salary2->sdi_limit : $sdi_total;

            $sdi_quoted = $salary2->sdi_quoted;
            $difference = round($sdi_aud - $sdi_quoted, 2);

            $salary2->update([
                'sdi_quoted' => $sdi_quoted,
                'difference' => $difference,
                'daily_bonus' => $daily_bonus,
                'sdi' => $sdi,
                'total_sdi' => floatval(round($sdi_total, 2)),
                'sdi_aud' => floatval(round($sdi_aud, 2)),
            ]);
        }
    }
}
