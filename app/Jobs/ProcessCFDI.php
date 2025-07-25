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

    public $timeout = 1200;
    public $tries = 25;

    public function __construct($user, $file, $year, $message,$company)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->company = $company;
        $this->message = $message;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //$data = Excel::import(new PlainDataImport($this->year), $this->file);
        Excel::queueImport(
            new RawCfdiImport($this->year, $this->user->id, $this->company->id),
            $this->file
        )->chain([
            new ProcessCfdiBatches($this->user->id, $this->year, $this->company),
            new SendCfdiNotification($this->user, $this->message)
        ]);
    }
}
