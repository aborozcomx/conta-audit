<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeePayroll;
use App\Models\EmployeePayrollConcept;
use App\Models\Vacation;
use App\Models\Uma;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Row;

class PlainDataImport implements OnEachRow, WithHeadingRow, WithChunkReading, WithStartRow
{
    protected $year;
    protected $companyCache = [];
    protected $vacationCache = [];

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        if (empty($row['uuid'])) {
            return; // Ignorar filas sin UUID
        }

        DB::transaction(function () use ($row) {
            $company = $this->getCompany($row['rfc_emisor']);
            if (!$company) {
                // Aquí puedes loggear o manejar el error si la compañía no existe
                return;
            }

            $employee = Employee::firstOrCreate(
                ['rfc' => $row['rfc_receptor']],
                [
                    'name' => $row['nombrereceptor'],
                    'clave' => $row['numempleado'],
                    'puesto' => $row['pueston'] ?? 'N/A',
                    'depto' => $row['departamento'] ?? 'N/A',
                    'curp' => '',
                    'age' => '',
                    'start_date' => $row['fechainiciorellaboral'],
                    'number' => $row['numempleado'],
                    'social_number' => $row['numseguridadsocial'],
                    'base_salary' => $row['salariobasecotapor'],
                    'daily_salary' => $row['salariodiariointegrado'],
                    'company_id' => $company->id,
                ]
            );

            if ($row['periodicidadpago'] == '04 - Quincenal' || $row['periodicidadpago'] == '04 - Quincenal') {
                $year = $this->year;
                $month = $this->getMonthFromPeriod($row['periodo']);

                $carbon = new Carbon("last day of $month $year");
                $start_date = new Carbon($employee->start_date);
                $age = $carbon->diff($start_date);

                $vacation = $this->getVacation($age->y);
                if (!$vacation) {
                    // Maneja si no hay vacaciones definidas para ese año
                    return;
                }

                $daily_bonus = round($row['salariobasecotapor'] * $company->vacation_days / 365, 2);
                $vacations_import = $row['salariobasecotapor'] * $vacation->days;
                $vacation_bonus = round($vacations_import * ($company->vacation_bonus / 100) / 365, 2);

                $yearUma = ((int) $row['periodo'] === 1) ? $year - 1 : $year;
                $sdi_tope = Uma::where('year', $yearUma)->first();
                $sdi_tope = $sdi_tope->balance * 25;

                $sdi = round($row['salariobasecotapor'] + $daily_bonus + $vacation_bonus, 2);

                $salary = EmployeeSalary::where('year', $year)
                    ->where('period', $row['periodo'])
                    ->where('employee_id', $employee->id)
                    ->first();

                $salaryData = [
                    'period' => $row['periodo'],
                    'year' => $year,
                    'employee_id' => $employee->id,
                    'category_id' => 1,
                    'daily_salary' => $row['salariobasecotapor'],
                    'daily_bonus' => $daily_bonus,
                    'vacations_days' => $vacation->days,
                    'vacations_import' => $vacations_import,
                    'vacation_bonus' => $vacation_bonus,
                    'sdi' => $sdi,
                    'total_sdi' => 0,
                    'sdi_limit' => $sdi_tope,
                ];

                if (!$salary) {
                    EmployeeSalary::create($salaryData);
                } else {
                    $salary->update($salaryData);
                }

                // Aquí agregas creación de nómina si la necesitas, igual que antes
                $payroll = EmployeePayroll::create([
                        'total' => $row['total'],
                        'folio' => $row['folio'],
                        'fecha_inicial' => $row['fechainicialpago'],
                        'fecha_final' => $row['fechafinalpago'],
                        'dias_pagados' => $row['numdiaspagados'],
                        'total_deduction' => $row['totaldeducciones'],
                        'total_others' => $row['totalotrospagos'],
                        'total_perception' => $row['totalpercepciones'],
                        'total_salary' => $row['totalsueldosper'],
                        'period' => $row['periodo'],
                        'employee_id' => $employee->id,
                        'subtotal' => $row['subtotal'],
                        'descuento' => $row['descuento'],
                        'moneda' => $row['moneda'],
                    ]);

                    foreach ($row as $key => $value) {

                        if (preg_match('/^p\d{2}_/', $key)) {
                            $parts = explode('_', $key, 2);

                            EmployeePayrollConcept::updateOrCreate([
                                'employee_id' => $employee->id,
                                'period' => $row['periodo'],
                                'year' => $year,
                            ], [
                                'code' => $parts[0],
                                'concepto' => $parts[1],
                                'amount' => $value,
                                'employee_payroll_id' => $payroll->id,
                                'company_id' => $company->id,
                                'is_exented' => false,
                                'is_taxed' => false,
                                'is_variable' => false,
                            ]);
                        }
                    }
            }
        });
    }

    protected function getCompany($rfc)
    {
        if (!isset($this->companyCache[$rfc])) {
            $this->companyCache[$rfc] = Company::where('rfc', $rfc)->first();
        }
        return $this->companyCache[$rfc];
    }

    protected function getVacation($years)
    {
        if (!isset($this->vacationCache[$years])) {
            $this->vacationCache[$years] = Vacation::where('years', $years)->where('category_id', 1)->first();
        }
        return $this->vacationCache[$years];
    }

    protected function getMonthFromPeriod($period)
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        return $months[$period] ?? 'January';
    }

    public function chunkSize(): int
    {
        return 500; // reduce si sigue consumiendo mucha memoria
    }

    public function startRow(): int
    {
        return 2;
    }
}
