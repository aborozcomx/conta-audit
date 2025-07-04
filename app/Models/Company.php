<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rfc',
        'company_name',
        'vacation_days',
        'vacation_bonus',
    ];

    public function company_patronals(): HasMany
    {
        return $this->hasMany(CompanyPatronal::class);
    }
}
