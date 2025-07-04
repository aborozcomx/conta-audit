<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'clave',
        'puesto',
        'company_id',
        'rfc',
        'curp',
        'age',
        'start_date',
        'number',
        'social_number',
        'depto',
        'base_salary',
        'daily_salary',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee_payrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function employee_salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function employee_quotas(): HasMany
    {
        return $this->hasMany(EmployeeQuota::class);
    }

    public function employee_payroll_concepts(): HasMany
    {
        return $this->hasMany(EmployeePayrollConcept::class);
    }
}
