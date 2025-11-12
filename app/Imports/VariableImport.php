<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeQuota;
use App\Models\JobProgress;
use App\Models\Uma;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class VariableImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    protected $year;

    protected $progressId;

    protected $totalRows;

    protected $processedRows = 0;

    protected $errors = [];

    protected $employeesCache = [];

    protected $umas = [];

    protected $salaryBuffer = [];

    protected $quotaBuffer = [];

    public function __construct($year, $progressId = null)
    {
        $this->year = $year;
        $this->progressId = $progressId;

        // Pre-cargar UMA
        $this->umas = Uma::whereIn('year', [$year, $year - 1])->get()->keyBy('year');

        // Pre-cargar empleados
        $this->employeesCache = Employee::with('company')->get()->keyBy('social_number');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();
        $this->processedRows++;

        if (empty($row['sdi'])) {
            return;
        }

        $year = $this->year;
        $period = $row['periodo'];
        $yearUma = ($period == 1) ? $year - 1 : $year;
        $uma = $this->umas[$yearUma] ?? null;

        if (! $uma) {
            $this->addError("UMA no encontrada para año $yearUma");

            return;
        }

        $employee = $this->employeesCache[$row['nss']] ?? null;
        if (! $employee) {
            $this->addError("Empleado con NSS {$row['nss']} no encontrado");
            Log::info("NSS: {$row['nss']}, Employee: {$employee}");

            return;
        }

        // --- Actualizar salario ---
        $salary = $employee->employee_salaries->where('period', $period)->where('year', $year)->first();

        if ($salary) {
            Log::info("Employee: {$employee}, Salary: {$salary}");
            $sdi_quoted = $row['sdi'];
            $sdi_aud = $salary->sdi_aud;
            $difference = round($sdi_aud - $sdi_quoted, 2);

            $salary->update([
                'sdi_quoted' => $sdi_quoted,
                'difference' => $difference,
            ]);

            // --- Calcular cuotas ---
            $days = (int) $row['dias'];
            $absence = (int) ($row['aus'] ?? 0);
            $incapacity = (int) ($row['inc'] ?? 0);
            $total_days = $days - $absence - $incapacity;
            $days_incapacity = $days - $incapacity;
            $base_salary = $sdi_aud;

            $salary_incapacity = round($base_salary * $days_incapacity, 2);
            $salary_uma_bonus = round($uma->balance * $employee->company->vacation_bonus * $days_incapacity, 2);

            $base_price_em = round($base_salary * $total_days, 2);
            $base_price_rt = $base_price_em;
            $base_price_iv = min($salary_incapacity, $salary_uma_bonus);

            $salary_uma = round($uma->balance * $days_incapacity, 2);
            $fixed_price = round($salary_uma * 0.2040, 2);
            $sdmg = $base_salary > $uma->balance * 3
                ? round(($base_price_em - $uma->balance * 3 * $total_days) * 0.015, 2)
                : 0;
            $in_cash = round($base_price_em * 0.0095, 2);
            $disability_health = round($base_price_iv * 0.02375, 2);
            $pensioners = round($base_price_em * 0.01425, 2);
            $risk = $row['prima'] / 100;
            $risk_price = round($base_price_rt * $risk, 2);
            $nurseries = round($base_price_rt * 0.01, 2);

            $total_audit = $fixed_price + $sdmg + $in_cash + $disability_health + $pensioners + $risk_price + $nurseries;
            $subtotal = (float) $row['cuota_imss'];

            $this->quotaBuffer[] = [
                'period' => $period,
                'year' => $year,
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'base_salary' => $base_salary,
                'days' => $days,
                'absence' => $absence,
                'incapacity' => $incapacity,
                'total_days' => $total_days,
                'difference_days' => $days - $total_days,
                'base_price_em' => $base_price_em,
                'base_price_rt' => $base_price_rt,
                'base_price_iv' => $base_price_iv,
                'fixed_price' => $fixed_price,
                'sdmg' => $sdmg,
                'in_cash' => $in_cash,
                'disability_health' => $disability_health,
                'pensioners' => $pensioners,
                'risk_price' => $risk_price,
                'nurseries' => $nurseries,
                'total_audit' => $total_audit,
                'total_company' => $subtotal,
                'difference' => round($total_audit - $subtotal, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (count($this->quotaBuffer) >= 500) {
            $this->flushQuotas();
        }

        if ($this->progressId && $this->processedRows % 500 === 0) {
            $this->updateProgress();
        }
    }

    protected function flushQuotas()
    {
        if (! empty($this->quotaBuffer)) {
            EmployeeQuota::upsert(
                $this->quotaBuffer,
                ['employee_id', 'year', 'period'], // claves únicas
                array_keys($this->quotaBuffer[0] ?? [])
            );
            $this->quotaBuffer = [];
        }
    }

    protected function addError($error)
    {
        $this->errors[] = [
            'fila' => $this->processedRows,
            'error' => $error,
            'timestamp' => now()->toDateTimeString(),
        ];

        if (count($this->errors) > 100) {
            $this->errors = array_slice($this->errors, -100);
        }
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
            'message' => "Procesando Cuotas IMSS ({$this->processedRows}/{$this->totalRows})",
        ]);
    }

    public function finalize()
    {
        $this->flushQuotas();
        $this->updateProgress();
    }
}
