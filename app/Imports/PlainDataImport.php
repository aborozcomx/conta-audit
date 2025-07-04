<?php

namespace App\Imports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Models\Employee;
use App\Models\EmployeePayrollConcept;
use App\Models\EmployeePayroll;
use App\Models\EmployeeSalary;
use App\Models\Uma;
use App\Models\User;
use App\Models\Vacation;
use App\Models\Variable;
use Carbon\Carbon;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Notifications\PushNotification;

HeadingRowFormatter::default('none');

class PlainDataImport implements ToCollection, WithHeadingRow, WithChunkReading, WithStartRow
{
    /**
     * @param Collection $collection
     */

    public $year;
    public $variables;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if ($row['UUID']) {

                $company = Company::where('rfc', $row['RFC emisor'])->first();
                $employee = Employee::where('rfc', $row['RFC receptor'])->first();

                if (!$employee) {
                    $employee = Employee::create([
                        'name' => $row['Razon receptor'],
                        'clave' => $row['Num empleado'],
                        'puesto' => $row['Puesto'] ?? 'N/A',
                        'depto' => $row['Departamento'] ?? 'N/A',
                        'rfc' => $row['RFC receptor'],
                        'curp' => '',//$row['CURP'],
                        'age' => '', //$row['Antiguedad'],
                        'start_date' => $row['Fecha inicio relacion laboral'],
                        'number' => $row['Num empleado'],
                        'social_number' => $row['No Seguro social'],
                        'base_salary' => $row['Salario base cot apor'],
                        'daily_salary' => $row['Salario diario integrado'],
                        'company_id' => $company->id
                    ]);
                }

                if ($row['Periodicidad pago'] == 04 || $row['Periodicidad pago'] == '04')
                {
                    $employeeID = $employee;

                    $year = $this->year;
                    $month = getMonths($row['Periodo'] - 1);

                    $carbon = new Carbon("last day of $month $year");
                    $start_date = new Carbon($employee->start_date);

                    $age = $carbon->diff($start_date);

                    $vacationYear = $age->y;

                    $vacation = Vacation::where('years', $vacationYear)->where('category_id', 1)->first();


                    if (!$vacation) {
                        dd($age->y);
                    }

                    // revisar dias de vacaciones
                    $daily_bonus = round($row['Salario base cot apor'] * $company->vacation_days / 365, 2);
                    $vacations_import = $row['Salario base cot apor'] * $vacation->days;
                    $vacation_bonus = round($vacations_import * ($company->vacation_bonus / 100) / 365, 2);


                    // Año ajustado solo para UMA
                    $yearUma = ((int) $row['Periodo'] === 1) ? $year - 1 : $year;

                    $sdi_tope = Uma::where('year', $yearUma)->first();

                    $sdi_tope = $sdi_tope->balance * 25;

                    $sdi = round($row['Salario base cot apor'] + $daily_bonus + $vacation_bonus, 2);

                    $salary = EmployeeSalary::where('year', $year)->where('period', $row['Periodo'])->where('employee_id', $employeeID->id)->first();

                    if (!$salary) {
                        $salary = EmployeeSalary::create([
                            'period' => $row['Periodo'],
                            'year' => $year,
                            'employee_id' => $employeeID->id,
                            'category_id' => 1,
                            'daily_salary' => $row['Salario base cot apor'],
                            'daily_bonus' => $daily_bonus,
                            'vacations_days' => $vacation->days,
                            'vacations_import' => $vacations_import,
                            'vacation_bonus' => $vacation_bonus,
                            'sdi' => $sdi,
                            'total_sdi' => 0,
                            'sdi_limit' => $sdi_tope
                        ]);
                    } else {
                        $salary->update([
                            'period' => $row['Periodo'],
                            'year' => $year,
                            'employee_id' => $employeeID->id,
                            'category_id' => 1,
                            'daily_salary' => $row['Salario base cot apor'],
                            'daily_bonus' => $daily_bonus,
                            'vacations_days' => $vacation->days,
                            'vacations_import' => $vacations_import,
                            'vacation_bonus' => $vacation_bonus,
                            'sdi' => $sdi,
                            'total_sdi' => 0,
                            'sdi_limit' => $sdi_tope
                        ]);
                    }

                    $payroll = EmployeePayroll::create([
                        'total' => 0, //$row['Total'],
                        'folio' => $row['Folio'],
                        'fecha_inicial' => '',//$row['Fecha inicial pago'],
                        'fecha_final' => '',//$row['Fecha final pago'],
                        'dias_pagados' => $row['Dias pagados'],
                        'total_deduction' => 0, //$row['Total deducciones'],
                        'total_others' => 0, //$row['Total otros pagos'],
                        'total_perception' => 0, //$row['Total percepciones'],
                        'total_salary' => 0, //$row['Total sueldos'],
                        'period' => $row['Periodo'],
                        'employee_id' => $employeeID->id,
                    ]);

                }
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
