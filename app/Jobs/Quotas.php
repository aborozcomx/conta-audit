<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\VariableImport;
use App\Jobs\ProcessQuotasBatches;
use App\Jobs\SendCfdiNotification;
use App\Jobs\TruncateCFDISImports;

class Quotas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $user;
    private $file;
    private $year;
    private $message;
    private $company;
    private $uuid;

    public $timeout = 1200;
    public $tries = 25;

    public function __construct($user, $file, $year, $message, $company, $uuid)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->message = $message;
        $this->company = $company;
        $this->uuid = $uuid;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Excel::queueImport(
            //new RawQuotasImport($this->year),
            //new RawQuotasImport($this->year, $this->user->id, $this->company->id, $this->uuid),
            new VariableImport($this->year),
            $this->file
        )->chain([
            new SendCfdiNotification($this->user, $this->message),
        ]);
    }
}
