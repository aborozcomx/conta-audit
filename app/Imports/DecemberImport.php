<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Models\Employee;
use App\Models\EmployeeQuota;
use App\Models\Uma;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class DecemberImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    /**
     * @param Collection $collection
     */

    public $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy('nss')->map(function ($group) {
            return [
                'dias' => $group->sum(function ($item) {
                    $value = $item['dias'] ?? 0;
                    return is_numeric($value) ? intval($value) : 0;
                }),
                'aus' => $group->sum(function ($item) {
                    $value = $item['aus'] ?? 0;
                    return is_numeric($value) ? intval($value) : 0;
                }),
                'inc' => $group->sum(function ($item) {
                    $value = $item['inc'] ?? 0;
                    return is_numeric($value) ? intval($value) : 0;
                })
            ];
        });


        foreach ($rows as $row) {
            if ($row['sdi']) {
                $year = $this->year;
                $uma = Uma::where('year', $year)->first();
                $employee = Employee::where('social_number', $row['nss'])->first();



                if ($employee) {
                    $days = $grouped[$row['nss']]['dias'];
                    $salary = $employee->employee_salaries->where('year', $year)->where('period', 12)->first();

                    if($salary) {
                        $sdi_quoted = $row['sdi'];//$salary->sdi;
                        $sdi_aud = $salary->sdi_aud;
                        $difference = round($sdi_aud - $sdi_quoted, 2);

                        $salary->update([
                            'sdi_quoted' => $sdi_quoted,
                            'difference' => $difference,
                        ]);

                        $base_salary = $sdi_quoted; //$salary->sdi ?? 0;
                        $absence = $grouped[$row['nss']]['aus'] ?? 0;
                        $incapacity = $grouped[$row['nss']]['inc'] ?? 0;

                        $total_days = $days - $absence - $incapacity;
                        $difference_days = $days - $total_days;
                        $days_incapacity = $days - $incapacity;

                        $salary_incapacity = round($base_salary * $days_incapacity, 2);
                        $salary_uma_bonus = round($uma->balance * $employee->company->vacation_bonus * $days_incapacity, 2);

                        $base_price_em = round($base_salary * $total_days, 2);
                        $base_price_rt = round($base_salary * $total_days, 2);
                        $base_price_iv = $salary_incapacity <= $salary_uma_bonus ? $salary_incapacity : $salary_uma_bonus;

                        $salary_uma = round($uma->balance * $days_incapacity, 2);
                        $fixed_price = round($salary_uma * .2040, 2);
                        $sdmg = $base_salary > $uma->balance * 3 ? round(($base_price_em - $uma->balance * 3 * $total_days) * .015, 2) : 0;
                        $in_cash = round($base_price_em * .00950, 2);

                        $disability_health = round($base_price_iv * .023750, 2);
                        $pensioners = round($base_price_em * .014250, 2);

                        $risk = $row['prima'] / 100;
                        $risk_price = round($base_price_rt * $risk, 2);
                        $nurseries = round($base_price_rt * .01, 2);

                        $total_audit = $fixed_price + $sdmg + $in_cash + $disability_health + $pensioners + $risk_price + $nurseries;

                        $subtotal = $row['cuota_imss']; //$grouped[$row['nss']];
                        $quota = EmployeeQuota::updateOrCreate([
                            'period' => 12,
                            'year' => $year,
                            'employee_id' => $employee->id,
                        ],[
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
                            'difference' => round($total_audit - $subtotal,2)
                        ]);
                    }
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
