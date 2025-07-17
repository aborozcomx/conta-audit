<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PlainDataImport;



class ProcessCFDI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $user;
    private $file;
    private $year;

    public $timeout = 1200;
    public $tries = 25;

    public function __construct($user, $file, $year)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = Excel::import(new PlainDataImport($this->year), $this->file);
    }
}
