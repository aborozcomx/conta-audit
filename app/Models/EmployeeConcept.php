<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'concepto',
        'amount',
        'employee_id',
    ];
}
