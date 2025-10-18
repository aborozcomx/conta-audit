<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobProgress extends Model
{
    protected $fillable = [
        'job_id', 'type', 'total_rows', 'processed_rows',
        'progress_percentage', 'status', 'message', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';
}
