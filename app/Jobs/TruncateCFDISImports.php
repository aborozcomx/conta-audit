<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\CfdiImport;
use App\Models\Company;

class TruncateCFDISImports implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $year, public Company $company, public string $uuid) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        CfdiImport::where('year', $this->year)->where('company_id', $this->company)->where('uuid', $this->uuid)->truncate();
    }
}
