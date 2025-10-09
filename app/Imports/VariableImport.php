<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeQuota;
use App\Models\Uma;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class VariableImport implements OnEachRow, ShouldQueue, WithChunkReading, WithHeadingRow // , SkipsOnError, SkipsOnFailure
{
    // use SkipsFailures;
    /**
     * @param  Collection  $collection
     */
    public $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        if ($row['sdi']) {
            $year = $this->year;
            $yearUma = ((int) $row['periodo'] === 1) ? $year - 1 : $year;

            $uma = Uma::where('year', $yearUma)->first();

            $employee = Employee::where('social_number', $row['nss'])->first();
            $employee = Employee::with([
                'company',
                'employee_salaries' => fn ($q) => $q->where('year', $year)->where('period', $row['periodo'])->orderBy('created_at', 'desc'),
            ])->where('social_number', $row['nss'])->first();

            if ($employee) {

                // $salary = $employee->employee_salaries->where('year', $year)->where('period', $row['periodo'])->first();
                $salary = $employee->employee_salaries->first();

                if ($salary) {
                    $sdi_quoted = $row['sdi'];
                    $sdi_aud = $salary->sdi_aud;
                    $difference = round($sdi_aud - $sdi_quoted, 2);

                    $salary->update([
                        'sdi_quoted' => $sdi_quoted,
                        'difference' => $difference,
                    ]);

                    $days = (int) $row['dias'];
                    $absence = (int) ($row['aus'] ?? 0);
                    $incapacity = (int) ($row['inc'] ?? 0);

                    $base_salary = $sdi_aud;
                    $total_days = $days - $absence - $incapacity;
                    $difference_days = $days - $total_days;
                    $days_incapacity = $days - $incapacity;

                    $salary_incapacity = round($base_salary * $days_incapacity, 2);
                    $salary_uma_bonus = round($uma->balance * $employee->company->vacation_bonus * $days_incapacity, 2);

                    $base_price_em = round($base_salary * $total_days, 2);
                    $base_price_rt = $base_price_em;
                    $base_price_iv = min($salary_incapacity, $salary_uma_bonus);

                    $salary_uma = round($uma->balance * $days_incapacity, 2);
                    $fixed_price = round($salary_uma * .2040, 2);
                    $sdmg = $base_salary > $uma->balance * 3
                        ? round(($base_price_em - $uma->balance * 3 * $total_days) * .015, 2)
                        : 0;
                    $in_cash = round($base_price_em * .0095, 2);
                    $disability_health = round($base_price_iv * .02375, 2);
                    $pensioners = round($base_price_em * .01425, 2);
                    $risk = $row['prima'] / 100;
                    $risk_price = round($base_price_rt * $risk, 2);
                    $nurseries = round($base_price_rt * .01, 2);

                    $total_audit = $fixed_price + $sdmg + $in_cash + $disability_health + $pensioners + $risk_price + $nurseries;
                    $subtotal = (float) $row['cuota_imss'];

                    $batch = EmployeeQuota::create([
                        'period' => $row['periodo'],
                        'year' => $year,
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'base_salary' => $base_salary,
                        'days' => $days,
                        'absence' => $absence,
                        'incapacity' => $incapacity,
                        'total_days' => $total_days,
                        'difference_days' => $difference_days,
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
                    ]);
                }
            }

        }
    }

    // public function onError(Throwable $e)
    // {
    //     // Log general errors during import (e.g., file reading issues)
    //     Log::error('Laravel Excel Import Error: ' . $e->getMessage());
    // }

    // /**
    //  * @param Failure[] $failures
    //  */
    // public function onFailure(Failure ...$failures)
    // {
    //     foreach ($failures as $failure) {
    //         // Get the row number where the failure occurred
    //         $row = $failure->row();
    //         // Get the attribute that failed validation (e.g., 'email', 'name')
    //         $attribute = $failure->attribute();
    //         // Get the error messages for the failed attribute
    //         $errors = implode(', ', $failure->errors());
    //         // Get the original row data
    //         $values = json_encode($failure->values());

    //         // Log the failure details
    //         Log::warning("Laravel Excel Import Failure on Row {$row}, Attribute '{$attribute}': {$errors}. Values: {$values}");
    //     }
    // }

    public function chunkSize(): int
    {
        return 500;
    }
}
