<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'year',
        'employee_id',
        'base_salary',
        'days',
        'absence',
        'incapacity',
        'total_days',
        'difference_days',
        'base_price_em',
        'base_price_rt',
        'base_price_iv',
        'fixed_price',
        'sdmg',
        'in_cash',
        'disability_health',
        'pensioners',
        'risk_price',
        'nurseries',
        'total_audit',
        'total_company',
        'difference',
        'company_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
