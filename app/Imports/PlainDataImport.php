<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Uma;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class PlainDataImport implements OnEachRow, ShouldQueue, WithChunkReading, WithHeadingRow
{
    protected $year;

    protected $company;

    protected $vacationCache = [];

    public function __construct($year, $company)
    {
        $this->year = $year;
        $this->company = $company;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        $employee = Employee::firstOrCreate(
            ['social_number' => $row['numseguridadsocial']],
            [
                'name' => $row['nombrereceptor'],
                'clave' => $row['numempleado'],
                'puesto' => $row['pueston'] ?? 'N/A',
                'depto' => $row['departamento'] ?? 'N/A',
                'curp' => '',
                'age' => '',
                'rfc' => $row['rfc_receptor'],
                'start_date' => $row['fechainiciorellaboral'],
                'number' => $row['numempleado'],
                'social_number' => $row['numseguridadsocial'],
                'base_salary' => $row['salariobasecotapor'],
                'daily_salary' => $row['salariobasecotapor'],
                'company_id' => $this->company->id,
            ]
        );

        if ($row['tiponomina'] == 'O - Ordinaria' || $row['tiponomina'] == 'O') {
            $year = $this->year;
            $month = $this->getMonthFromPeriod($row['periodo']);

            $carbon = new Carbon("last day of $month $year");
            $start_date = new Carbon($employee->start_date);
            $age = $carbon->diff($start_date);

            $vacation = $this->getVacation($age->y);
            if (! $vacation) {
                // Maneja si no hay vacaciones definidas para ese año
                Log::error('ProcessCFDI failed', [
                    'company_id' => $this->company,
                    'year' => $this->year,
                    'uuid' => $this->uuid,
                    'file' => $this->filePath,
                    'error' => $e->getMessage(),
                    'age' => $age,
                ]);
                // return;
            }

            $daily_bonus = round($row['salariobasecotapor'] * $this->company->vacation_days / 365, 2);
            $vacations_import = $row['salariobasecotapor'] * $vacation->days;
            $vacation_bonus = round($vacations_import * ($this->company->vacation_bonus / 100) / 365, 2);

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
                'company_id' => $this->company->id,
                'variables' => '{}',
            ];

            EmployeeSalary::create($salaryData);
        }

    }

    protected function getVacation($years)
    {
        if (! isset($this->vacationCache[$years])) {
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
        return 1000; // reduce si sigue consumiendo mucha memoria
    }
}
