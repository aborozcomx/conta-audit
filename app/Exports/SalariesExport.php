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

    public function __construct(string $year, string $period)
    {
        $this->year = $year;
        $this->period = $period;
    }

    public function view(): View
    {
        $salaries = EmployeeSalary::with('employee')->where('year', $this->year)->where('period', $this->period)->get();
        return view('exports.salaries', [
            'salaries' => $salaries
        ]);
    }
}
