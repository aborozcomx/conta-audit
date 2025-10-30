<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\JobProgress;
use App\Models\Uma;
use App\Models\Vacation;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class PlainDataImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    protected $year;

    protected $company;

    protected $progressId;

    protected $totalRows;

    protected $processedRows = 0;

    protected $errors = [];

    protected $employeesCache = [];

    protected $vacationCache = [];

    protected $umas = [];

    protected $salaryBuffer = [];

    public function __construct($year, $company, $progressId = null)
    {
        $this->year = $year;
        $this->company = $company;
        $this->progressId = $progressId;

        // Pre-cargar UMA
        $this->umas = Uma::whereIn('year', [$year, $year - 1])->get()->keyBy('year');

        // Pre-cargar empleados existentes
        $this->employeesCache = Employee::where('company_id', $company->id)->get()->keyBy('social_number');
    }

    public function chunkSize(): int
    {
        return 500; // chunks de 500 filas
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();
        $this->processedRows++;

        // --- EMPLOYEE ---
        $employee = $this->employeesCache[$row['numseguridadsocial']] ?? null;
        if (! $employee) {
            $employee = Employee::create([
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
            ]);
            $this->employeesCache[$employee->social_number] = $employee;
        }

        // --- SOLO NÓMINA ORDINARIA ---
        if (in_array($row['tiponomina'], ['O - Ordinaria', 'O'])) {
            $month = $this->getMonthFromPeriod($row['periodo']);
            $carbon = new Carbon("last day of $month $this->year");
            $age = $carbon->diff(new Carbon($employee->start_date));
            $vacation = $this->getVacation($age->y);

            $sdi_tope = ($this->umas[$this->year] ?? $this->umas[$this->year - 1])->balance * 25;

            $daily_bonus = round($row['salariobasecotapor'] * $this->company->vacation_days / 365, 2);
            $vacation_bonus = round(($row['salariobasecotapor'] * ($vacation->days ?? 0)) * ($this->company->vacation_bonus / 100) / 365, 2);

            $this->salaryBuffer[] = [
                'period' => $row['periodo'],
                'year' => $this->year,
                'employee_id' => $employee->id,
                'category_id' => 1,
                'daily_salary' => $row['salariobasecotapor'],
                'daily_bonus' => $daily_bonus,
                'vacations_days' => $vacation->days ?? 0,
                'vacations_import' => $row['salariobasecotapor'] * ($vacation->days ?? 0),
                'vacation_bonus' => $vacation_bonus,
                'sdi' => round($row['salariobasecotapor'] + $daily_bonus + $vacation_bonus, 2),
                'sdi_limit' => $sdi_tope,
                'company_id' => $this->company->id,
                'variables' => '{}',
            ];
        }

        // Insertar cada 500 filas
        if (count($this->salaryBuffer) >= 500) {
            $this->flushSalaries();
        }

        if ($this->progressId && $this->processedRows % 500 === 0) {
            $this->updateProgress();
        }
    }

    protected function flushSalaries()
    {
        if (! empty($this->salaryBuffer)) {
            EmployeeSalary::upsert(
                $this->salaryBuffer,
                ['employee_id', 'year', 'period', 'daily_salary'], // claves únicas
                ['daily_salary', 'daily_bonus', 'vacations_days', 'vacations_import', 'vacation_bonus', 'sdi', 'sdi_limit', 'variables']
            );
            $this->salaryBuffer = [];
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
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return $months[$period] ?? 'January';
    }

    public function setTotalRows($totalRows)
    {
        $this->totalRows = $totalRows;
        $this->updateProgress();
    }

    protected function updateProgress()
    {
        if (! $this->progressId) {
            return;
        }

        $percentage = $this->totalRows > 0 ? round(($this->processedRows / $this->totalRows) * 100) : 0;
        JobProgress::where('id', $this->progressId)->update([
            'processed_rows' => $this->processedRows,
            'progress_percentage' => $percentage,
            'message' => "Procesando CFDIS ({$this->processedRows}/{$this->totalRows})",
        ]);
    }

    public function finalize()
    {
        $this->flushSalaries();
        $this->updateProgress();
    }
}
