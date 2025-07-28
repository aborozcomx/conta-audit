<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiImport extends Model
{
    protected $fillable = [
        'data',
        'user_id',
        'company_id',
        'year',
        'uuid'
    ];
}
