<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\VariableImport;


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

    public $timeout = 1200;
    public $tries = 25;

    public function __construct($user, $file, $year, $message)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->message = $message;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Excel::queueImport(
            new VariableImport($this->year),
            $this->file
        )->chain([
            new SendCfdiNotification($this->user, $this->message),
        ]);
    }
}
