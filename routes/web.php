<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UmaController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\VariableController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('companies', CompanyController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('vacations', VacationController::class);
    Route::resource('umas', UmaController::class);
    Route::resource('variables', VariableController::class);
    Route::get('employee-salaries/{employee_salary}', [EmployeeController::class, 'editSalary'])->name('employee.salary');
    Route::patch('employee-salaries/{employee_salary}', [EmployeeController::class, 'updateSalary'])->name('employee.salaryUpdate');
    Route::post('employee-salaries/{employee}/addSalary', [EmployeeController::class, 'addSalary'])->name('employee.addSalary');
    Route::get('companies/{company}/importPlainFile', [CompanyController::class, 'import'])->name('companies.import');
    Route::get('companies/{company}/importImss', [CompanyController::class, 'importPayrolls'])->name('companies.importPayrolls');
    Route::get('companies/{company}/importVariables', [CompanyController::class, 'importVariables'])->name('companies.importVariables');
    Route::post('companies/{company}/file', [CompanyController::class, 'uploadFile'])->name('companies.files');
    Route::post('companies/{company}/payroll', [CompanyController::class, 'uploadFilePayrolls'])->name('companies.filesPayrolls');
    Route::post('companies/{company}/variables', [CompanyController::class, 'uploadVariablesFile'])->name('companies.filesVariables');
    Route::get('payrolls', [CompanyController::class, 'getPayrolls'])->name('companies.payrolls');
    Route::get('employees/{employee}/payrolls', [EmployeeController::class, 'getConcepts'])->name('employees.concepts');
    Route::get('payrolls/{employee_payroll}/concepts', [EmployeeController::class, 'getPayrollConcepts'])->name('employees.payrollConcepts');
    Route::get('salaries', [EmployeeController::class, 'show'])->name('employees.salaries');
    Route::post('salaries', [EmployeeController::class, 'saveVariables'])->name('employees.variables');
    Route::get('quotas', [EmployeeController::class, 'showQuotas'])->name('employees.showQuotas');
    Route::get('export', [EmployeeController::class, 'exportQuotas'])->name('employees.exportQuotas');
    Route::get('salaries/export', [EmployeeController::class, 'exportSalaries'])->name('employees.exportSalaries');
    Route::get('companies/{company}/patronals', [CompanyController::class, 'getCompanyPatronals'])->name('company.patronals');
    Route::get('companies/{company}/patronals/create', [CompanyController::class, 'createPatronal'])->name('company.createPatronal');
    Route::post('calculate', [EmployeeController::class, 'calculate'])->name('employees.calculate');
    Route::get('/company-variables/progress/{id}', [CompanyController::class, 'getProgress'])->name('company-variables.progress');
    Route::get('/company-variables/progress/{id}/bar', [CompanyController::class, 'getProgressBar'])->name('company-variables.progressBar');
});

Route::post('/save-subscription', function (Request $request) {
    $user = auth()->user(); // Asegúrate de que el usuario esté autenticado
    $user->updatePushSubscription(
        $request->endpoint,
        $request->keys['p256dh'],
        $request->keys['auth']
    );
});
require __DIR__.'/auth.php';
