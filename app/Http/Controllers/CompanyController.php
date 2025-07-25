<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Models\Company;
use App\Imports\VariableImport;
use App\Imports\CompanyVariable;
use App\Jobs\ProcessCFDI;
use App\Jobs\ProcessCompanyVariables;
use App\Jobs\Quotas;
use App\Jobs\SendCfdiNotification;
use App\Models\EmployeePayroll;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CompanyController extends Controller
{
    public function index(): Response
    {

        //phpinfo();
        return Inertia::render('Companies/Index', [
            'companies' => Company::all()
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Companies/Create');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Company::create($request->all());

        return Redirect::route('companies.index')->with('message', 'La empresa se ha guardado exitosamente.');
    }

    public function update(Company $company, Request $request): RedirectResponse
    {
        $company->update($request->all());

        return Redirect::route('companies.index')->with('message', 'La empresa se ha actualizado exitosamente.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return Redirect::route('companies.index')->with('message', 'La empresa se ha borrado exitosamente.');
    }

    public function import(Company $company): Response
    {
        return Inertia::render('Companies/Show', [
            'years' => getYears(),
            'company' => $company
        ]);
    }

    public function importPayrolls(Company $company): Response
    {
        return Inertia::render('Companies/ShowPayroll', [
            'years' => getYears(),
            'company' => $company,
        ]);
    }

    public function importVariables(Company $company): Response
    {
        return Inertia::render('Companies/ShowVariables', [
            'years' => getYears(),
            'company' => $company,
        ]);
    }


    public function uploadFile(Request $request, Company $company)
    {
        $filePath = $request->file('file')->store('imports/cfdis');
        $message = [
            'title' => 'CFDIS',
            'content' => 'Se ha terminado la importación de CFDIS'
        ];

        // ProcessCFDI::withChain([
        //      new SendCfdiNotification(auth()->user(), $message)
        // ])->dispatch(auth()->user(), storage_path('app/' . $filePath), $request->year);

        ProcessCFDI::dispatch(
            auth()->user(),
            storage_path('app/' . $filePath),
            $request->year,
            $message,
            $company
        );



        return Redirect::route('employees.salaries')->with('message', 'Importando CFDIS');
    }

    public function uploadVariablesFile(Request $request, Company $company)
    {
        $filePath = $request->file('file')->store('imports/variables');
        $message = [
            'title' => 'Variables',
            'content' => 'Se ha terminado la importación de las variables de la compañía'
        ];

        ProcessCompanyVariables::withChain([
             new SendCfdiNotification(auth()->user(), $message)
        ])->dispatch(storage_path('app/' . $filePath), $request->year, $company);



        return Redirect::route('employees.salaries')->with('message', 'Importando Variables de la CIA');
    }

    public function uploadFilePayrolls(Request $request, Company $company)
    {
        $filePath = $request->file('file')->store('quotas');
        $message = [
            'title' => 'Cuotas IMSS',
            'content' => 'Se ha terminado la importación de las cuotas IMSS'
        ];

        Quotas::withChain([
            new SendCfdiNotification(auth()->user(), $message)
        ])->dispatch(auth()->user(), storage_path('app/' . $filePath), $request->year);


        return Redirect::route('employees.showQuotas')->with('message', 'Importando Cuotas IMSS');
    }

    public function getPayrolls(Request $request): Response
    {
        $payrolls = EmployeePayroll::all();

        return Inertia::render('Employees/Payrolls', [
            'payrolls' => $payrolls
        ]);
    }

    public function getCompanyPatronals(Request $request, Company $company)
    {
        return Inertia::render('Companies/Patronals', [
            'patronals' => $company->company_patronals,
            'company' => $company
        ]);
    }

    public function createPatronal(Request $request, Company $company)
    {
        return Inertia::render('Companies/CreatePatronals', [
            'company' => $company
        ]);
    }
}
