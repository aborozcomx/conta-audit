<?php

namespace App\Exports;

use App\Models\EmployeeSalary;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

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
        $salaries = EmployeeSalary::with('employee')->where('year', $this->year)->where('period', $this->period)->where('company_id', $this->company)->get();
        return view('exports.salaries', [
            'salaries' => $salaries
        ]);
    }
}
