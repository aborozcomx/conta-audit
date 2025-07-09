<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePayrollConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'concepto',
        'amount',
        'employee_payroll_id',
        'period',
        'year',
        'employee_id',
        'company_id',
        'is_exented',
        'is_taxed',
        'is_variable'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
