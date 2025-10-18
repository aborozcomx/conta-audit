<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCFDI;
use App\Jobs\ProcessCompanyVariables;
use App\Jobs\Quotas;
use App\Jobs\SendCfdiNotification;
use App\Models\Company;
use App\Models\EmployeePayroll;
use App\Models\JobProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {

        // phpinfo();
        return Inertia::render('Companies/Index', [
            'companies' => Company::all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Companies/Create');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company,
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
            'company' => $company,
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
        $uuid = Str::uuid();
        $message = [
            'title' => 'CFDIS',
            'content' => 'Se ha terminado la importación de CFDIS',
        ];

        // ProcessCFDI::withChain([
        //      new SendCfdiNotification(auth()->user(), $message)
        // ])->dispatch(auth()->user(), storage_path('app/' . $filePath), $request->year);

        // Crear registro de progreso
        $jobProgress = JobProgress::create([
            'job_id' => $uuid,
            'type' => 'CDFIS',
            'total_rows' => 0,
            'processed_rows' => 0,
            'progress_percentage' => 0,
            'status' => JobProgress::STATUS_PROCESSING,
            'message' => 'Iniciando procesamiento...',
        ]);

        ProcessCFDI::withChain([
            new SendCfdiNotification(auth()->user(), $message),
        ])->dispatch(auth()->user()->id, $filePath, $request->year, $message, $company->id, $uuid, $jobProgress->id);

        // return Redirect::route('employees.salaries')->with('message', 'Importando CFDIS');
        return Redirect::route('company-variables.progress', $jobProgress->id)->with('message', 'Importando CFDIS de la CIA');
    }

    public function uploadVariablesFile(Request $request, Company $company)
    {
        $filePath = $request->file('file')->store('imports/variables');
        $message = [
            'title' => 'Variables',
            'content' => 'Se ha terminado la importación de las variables de la compañía',
        ];

        // Crear registro de progreso
        $jobProgress = JobProgress::create([
            'job_id' => Str::uuid(),
            'type' => 'Variables CIA',
            'total_rows' => 0,
            'processed_rows' => 0,
            'progress_percentage' => 0,
            'status' => JobProgress::STATUS_PROCESSING,
            'message' => 'Iniciando procesamiento...',
        ]);

        ProcessCompanyVariables::withChain([
            new SendCfdiNotification(auth()->user(), $message),
        ])->onQueue('default')->dispatch(storage_path('app/'.$filePath), $request->year, $company, $jobProgress->id);

        // ProcessCompanyVariables::dispatch(
        //     storage_path('app/' . $filePath),
        //     $request->year,
        //     auth()->user()->id,
        //     $message
        // );

        return Redirect::route('company-variables.progress', $jobProgress->id)->with('message', 'Importando Variables de la CIA');
    }

    public function getProgress($id)
    {
        $progress = JobProgress::findOrFail($id);

        return Inertia::render('Progress/Show', [
            'progressId' => $progress->id,
        ]);
    }

    public function getProgressBar($id)
    {
        $progress = JobProgress::findOrFail($id);

        return response()->json([
            'percentage' => $progress->progress_percentage,
            'message' => $progress->message,
            'type' => $progress->type,
            'status' => $progress->status,
            'metadata' => $progress->metadata,
            'created_at' => $progress->created_at->toISOString(),
            'updated_at' => $progress->updated_at->toISOString(),
        ]);
    }

    public function uploadFilePayrolls(Request $request, Company $company)
    {
        $filePath = $request->file('file')->store('quotas');
        $message = [
            'title' => 'Cuotas IMSS',
            'content' => 'Se ha terminado la importación de las cuotas IMSS',
        ];

        $uuid = Str::uuid();

        // Crear registro de progreso
        $jobProgress = JobProgress::create([
            'job_id' => $uuid,
            'type' => 'Cuotas IMSS',
            'total_rows' => 0,
            'processed_rows' => 0,
            'progress_percentage' => 0,
            'status' => JobProgress::STATUS_PROCESSING,
            'message' => 'Iniciando procesamiento...',
        ]);

        // Quotas::dispatch(
        //     auth()->user()->id,
        //     storage_path('app/'.$filePath),
        //     $request->year,
        //     $message,
        //     $company->id,
        //     $uuid
        // );

        Quotas::withChain([
            new SendCfdiNotification(auth()->user(), $message),
        ])->onQueue('cuotas')->dispatch(auth()->user()->id, $filePath, $request->year, $message, $company->id, $uuid, $jobProgress->id);

        // ProcessCompanyVariables::dispatch(
        //     storage_path('app/' . $filePath),
        //     $request->year,
        //     auth()->user()->id,
        //     $message
        // );

        return Redirect::route('company-variables.progress', $jobProgress->id)->with('message', 'Importando Cuotas IMSS de la CIA');

    }

    public function getPayrolls(Request $request): Response
    {
        $payrolls = EmployeePayroll::all();

        return Inertia::render('Employees/Payrolls', [
            'payrolls' => $payrolls,
        ]);
    }

    public function getCompanyPatronals(Request $request, Company $company)
    {
        return Inertia::render('Companies/Patronals', [
            'patronals' => $company->company_patronals,
            'company' => $company,
        ]);
    }

    public function createPatronal(Request $request, Company $company)
    {
        return Inertia::render('Companies/CreatePatronals', [
            'company' => $company,
        ]);
    }
}
