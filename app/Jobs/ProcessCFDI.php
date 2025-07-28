<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PlainDataImport;
use App\Imports\RawCfdiImport;
use App\Jobs\ProcessCfdiBatches;
use App\Jobs\SendCfdiNotification;
use App\Jobs\TruncateCFDISImports;



class ProcessCFDI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $user;
    private $file;
    private $year;
    private $company;
    private $message;
    private $uuid;

    public $timeout = 1200;
    public $tries = 25;

    public function __construct($user, $file, $year, $message,$company,$uuid)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->company = $company;
        $this->message = $message;
        $this->uuid = $uuid;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //$data = Excel::import(new PlainDataImport($this->year), $this->file);
        Excel::queueImport(
            new RawCfdiImport($this->year, $this->user->id, $this->company->id, $this->uuid),
            $this->file
        )->chain([
            new ProcessCfdiBatches($this->user->id, $this->year, $this->company, $this->uuid),
            new SendCfdiNotification($this->user, $this->message),
            new TruncateCFDISImports($this->year, $this->company, $this->uuid),
        ]);
    }
}
