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
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\User;


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
    public $tries = 12;

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

    public function tags(): array
    {
        return [
            'cfdi',
            "company:{$this->company}",
            "year:{$this->year}",
            "uuid:{$this->uuid}",
        ];
    }

    public function handle(): void
    {
        //$data = Excel::import(new PlainDataImport($this->year), $this->file);

        $company2 = Company::query()->findOrFail($this->company);
        /** @var User $user */
        $user2 = User::query()->findOrFail($this->user);

        Excel::queueImport(
            //new RawCfdiImport($this->year, $this->user->id, $this->company->id, $this->uuid),
            new PlainDataImport($this->year, $company2,1000),
            $this->file
        )
        ->onQueue('cfdis')
        ->chain([
            (new SendCfdiNotification($user2, $this->message))->onQueue('notifications'),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCFDI failed', [
            'company_id' => $this->company,
            'year' => $this->year,
            'uuid' => $this->uuid,
            'file' => $this->filePath,
            'error' => $e->getMessage(),
        ]);

        // Podrías notificar aquí también, o re-enfiletar una alerta
        // SendAdminAlert::dispatch(...);
    }
}
