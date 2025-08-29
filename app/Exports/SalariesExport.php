<?php

namespace App\Exports;

use App\Models\EmployeeSalary;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\DB;

class SalariesExport implements FromView
{

    use Exportable;

    private $year;
    private $period;
    private $company;

    public function __construct(string $year, string $period, int $company)
    {
        $this->year = $year;
        $this->period = $period;
        $this->company = $company;
    }

    public function view(): View
    {
        // $salaries = EmployeeSalary::with(['employee' => function($q) {
        //     $q->orderBy('name');
        // }])->where('year', $this->year)->where('period', $this->period)->where('company_id', $this->company)->get();
        $salaries = DB::table('employee_salaries')
            ->selectRaw('employee_salaries.*, employees.name, employees.rfc, employees.start_date,employees.social_number')
            ->join('employees', 'employees.id', '=', 'employee_salaries.employee_id')
            ->where('employee_salaries.period', $this->period)
            ->where('employee_salaries.year', $this->year)
            ->where('employee_salaries.company_id', $this->company)
            ->orderBy('employees.name')
            ->get();

        return view('exports.salaries', [
            'salaries' => $salaries
        ]);
    }
}
