<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Models\Employee;
use App\Models\Company;
use App\Models\EmployeeConcept;
use App\Models\EmployeePayroll;
use App\Models\Vacation;
use App\Models\EmployeePayrollConcept;
use App\Models\EmployeeSalary;
use App\Models\EmployeeQuota;
use App\Models\Uma;
use Carbon\Carbon;
use App\Exports\QuotasExport;
use App\Exports\SalariesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendCfdiNotification;
use App\Jobs\CalculateDifference;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Employees/Index', [
            'employees' => Employee::with('company')->get()
        ]);
    }

    public function show(Request $request, Employee $employee): Response
    {
        $year = '2023';
        $period = "1";
        $month = getMonths($period);
        $companies = Company::all();
        $company = Company::first();
        $company = $company->id;

        if ($request->query('year')) {
            $year = $request->query('year');
        }

        if ($request->query('period')) {
            $period = $request->query('period');
        }

        if ($request->query('company')) {
            $company = $request->query('company');
        }

        $salaries = EmployeeSalary::with('employee')->where('year', $year)->where('period', $period)->whereRelation('employee', 'company_id', $company)->get();

        $variables = DB::table('employee_payroll_concepts')
        ->select(DB::raw('concepto, SUM(amount) as total'))
        ->where('year', $year)
        ->groupBy(['concepto'])
        ->get();

        return Inertia::render('Employees/Show', [
            'salaries' => $salaries,
            'years' => getYears(),
            'year' => $year,
            'period' => $period,
            'companies' => $companies,
            'company' => strval($company),
            'variables' => $variables
        ]);
    }

    public function calculate(Request $request, Employee $employee): RedirectResponse
    {

        $year = $request->year;
        $period = $request->period;
        $company = $request->company;

        $message = [
            'title' => 'SDI',
            'content' => 'Se ha terminado el cálculo de SDI'
        ];

        CalculateDifference::withChain([
             new SendCfdiNotification(auth()->user(), $message)
        ])->dispatch( $company,$period, $year);


        return redirect()->back()->with('message', 'Calculando diferencia...');
    }

    public function saveVariables(Request $request) {
        $year = $request->year;
        $company = $request->company;
        $variables = $request->variables;

        $results = DB::table('employee_payroll_concepts')
            ->select(DB::raw('*'))
            ->where('company_id', $company)
            ->where('year', $year)
            ->where(function ($query) use ($variables) {
                foreach ($variables as $term) {
                    $query->orWhere('concepto', $term);
                }
            })
            ->get();


        $concepts = EmployeePayrollConcept::where('company_id', $company)->where('year', $year)->whereIn('concepto', $variables)->get();

        foreach($concepts as $concept) {
            $concept->update([
                'is_variable' => true
            ]);
        }


        return redirect()->back()->with('message', 'Las variables se han guardado correctamente');
    }

    public function showQuotas(Request $request): Response
    {
        $year = '2023';
        $period = "1";
        $month = getMonths($period);

        $companies = Company::all();
        $company = Company::first();
        $company = $company->id;

        if ($request->query('year')) {
            $year = $request->query('year');
        }

        if ($request->query('period')) {
            $period = $request->query('period');
        }

        if ($request->query('company')) {
            $company = $request->query('company');
        }

        $quotas = EmployeeQuota::with('employee')->where('year', $year)->where('period', $period)->whereRelation('employee', 'company_id', $company)->get();

        $grouped = $quotas->groupBy('employee.social_number')->map(function ($row) {
            return $row->sum('difference');
        });

        return Inertia::render('Employees/ShowQuotas', [
            'quotas' => $quotas,
            'years' => getYears(),
            'year' => $year,
            'period' => $period,
            'companies' => $companies,
            'company' => strval($company),
            'grouped' => $grouped
        ]);
    }

    public function exportQuotas(Request $request)
    {
        $year = $request->year;
        $period = $request->period;

        $month = getMonths($period - 1);



        return Excel::download(new QuotasExport($year, $period), 'IMSS.xlsx');
    }

    public function exportSalaries(Request $request)
    {
        $year = $request->year;
        $period = $request->period;

        $month = getMonths($period - 1);


        return Excel::download(new SalariesExport($year, $period), 'sdi.xlsx');
    }

    public function create(): Response
    {
        return Inertia::render('Employees/Create', [
            'companies' => Company::all()
        ]);
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'companies' => Company::all()
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Employee::create($request->all());

        return Redirect::route('employees.index');
    }

    public function update(Employee $employee, Request $request): RedirectResponse
    {
        $employee->update($request->all());

        return Redirect::route('employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return Redirect::route('employees.index');
    }

    public function getConcepts(Employee $employee)
    {
        return Inertia::render('Employees/Payrolls', [
            'employee' => $employee,
            'payrolls' => $employee->employee_payrolls
        ]);
    }

    public function getPayrollConcepts(EmployeePayroll $employeePayroll)
    {

        return Inertia::render('Employees/Concepts', [

            'concepts' => $employeePayroll->employee_payrolls_concept()->with('employee')->get()
        ]);
    }

    public function editSalary(EmployeeSalary $employee_salary)
    {
        return inertia('Employees/EditSalary', [
            'salary' => $employee_salary,
        ]);
    }

    public function addSalary(Request $request, Employee $employee)
    {
        $year = $request->year;
        $period = $request->period;
        $employee_salary = $employee->employee_salaries->where('year', $year)->where('period', $period - 1)->first();

        // dd($employee_salary);
        $year = $employee_salary->year;
        $month = getMonths($period - 1);

        $carbon = new Carbon("last day of $month $year");
        $start_date = new Carbon($employee->start_date);
        $age = $carbon->diff($start_date);

        $vacation = Vacation::where('years', $age->y)->where('category_id', 1)->first();

        $daily_bonus = round($employee_salary->daily_salary * $employee->company->vacation_days / 365, 2);
        $vacations_import = $employee_salary->daily_salary * $vacation->days;
        $vacation_bonus = round($vacations_import * ($employee->company->vacation_bonus / 100) / 365, 2);
        $sdi_tope = Uma::where('year', $year)->first();

        $sdi_tope = $sdi_tope->balance * 25;

        $sdi = round($employee_salary->daily_salary + $daily_bonus + $vacation_bonus, 2);

        $sdi_variable = $employee_salary->sdi_variable;
        $sdi_quoted = $employee_salary->sdi_quoted;

        $sdi_total = $employee_salary->sdi +  $sdi_variable;
        $sdi_aud = $sdi_total > $employee_salary->sdi_limit ? $employee_salary->sdi_limit : $sdi_total;
        $difference = round($sdi_aud - $sdi_quoted, 2);

        $salary = EmployeeSalary::create([
            'period' => $period,
            'year' => $year,
            'employee_id' => $employee->id,
            'category_id' => 1,
            'daily_salary' => $employee_salary->daily_salary,
            'daily_bonus' => $daily_bonus,
            'vacations_days' => $vacation->days,
            'vacations_import' => $vacations_import,
            'vacation_bonus' => $vacation_bonus,
            'sdi' => $sdi,
            'sdi_variable' => $sdi_variable,
            'total_sdi' => $sdi_total,
            'sdi_limit' => $sdi_tope,
            'sdi_aud' => $sdi_aud,
            'sdi_quoted' => $sdi_quoted,
            'difference' => $difference,
        ]);

        return Redirect::route('employees.show', ['employee' => $employee_salary->employee_id, 'year' => $employee_salary->year, 'period' => $period]);
    }

    public function updateSalary(Request $request, EmployeeSalary $employee_salary)
    {
        $sdi_variable = $request->sdi_variable;
        $sdi_quoted = $request->sdi_quoted;

        $sdi_total = $employee_salary->sdi +  $sdi_variable;


        $sdi_aud = $sdi_total > $employee_salary->sdi_limit ? $employee_salary->sdi_limit : $sdi_total;
        $difference = round($sdi_aud - $sdi_quoted, 2);

        $salary = $employee_salary->update([
            'sdi_variable' => $sdi_variable,
            'sdi_quoted' => $sdi_quoted,
            'total_sdi' => $sdi_total,
            'sdi_aud' => $sdi_aud,
            'difference' => $difference,
        ]);

        return Redirect::route('employees.show', ['employee' => $employee_salary->employee_id, 'year' => $employee_salary->year, 'period' => $employee_salary->period]);
    }
}
