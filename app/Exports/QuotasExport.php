<?php

namespace App\Exports;

use App\Models\EmployeeQuota;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\DB;

class QuotasExport implements FromView
{

    use Exportable;

    private $year;
    private $period;
    private $company;

    public function __construct(string $year, string $period, string $company)
    {
        $this->year = $year;
        $this->period = $period;
        $this->company = $company;
    }

    public function view(): View
    {
        // $quotas = EmployeeQuota::with(['employee' => function($q){
        //     $q->orderBy('name');
        // }])->where('year', $this->year)->where('period', $this->period)->where('company_id', $this->company)->get();
        $quotas = DB::table('employee_quotas')
            ->selectRaw('employee_quotas.*, employees.name, employees.rfc, employees.start_date,employees.social_number')
            ->join('employees', 'employees.id', '=', 'employee_quotas.employee_id')
            ->where('employee_quotas.period', $this->period)
            ->where('employee_quotas.company_id', $this-> company)
            ->where('employee_quotas.year', $this-> year)
            ->orderBy('employees.name')
            ->get();

        return view('exports.quotas', [
            'quotas' => $quotas
        ]);
    }
}
