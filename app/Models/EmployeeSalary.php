<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'year',
        'employee_id',
        'category_id',
        'daily_salary',
        'daily_bonus',
        'vacations_days',
        'vacations_import',
        'vacation_bonus',
        'sdi',
        'sdi_variable',
        'total_sdi',
        'sdi_limit',
        'sdi_aud',
        'sdi_quoted',
        'difference',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
