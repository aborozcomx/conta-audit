<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'subtotal',
        'descuento',
        'total',
        'moneda',
        'folio',
        'fecha_inicial',
        'fecha_final',
        'dias_pagados',
        'total_deduction',
        'total_others',
        'total_perception',
        'total_salary',
        'period',
        'employee_id'
    ];

    public function employee_payrolls_concept(): HasMany
    {
        return $this->hasMany(EmployeePayrollConcept::class, 'employee_payroll_id');
    }
}
