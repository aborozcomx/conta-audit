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
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\User;

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

    public $timeout = 1800;
    public $tries = 12;

    public function __construct($user, $file, $year, $message, $company, $uuid)
    {
        $this->user = $user;
        $this->file = $file;
        $this->year = $year;
        $this->message = $message;
        $this->company = $company;
        $this->uuid = $uuid;

    }

    public function tags(): array
    {
        return [
            'cuotas_imss',
            "company:{$this->company}",
            "year:{$this->year}",
            "uuid:{$this->uuid}",
        ];
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var User $user */
        $user2 = User::query()->findOrFail($this->user);

        Excel::queueImport(
            //new RawQuotasImport($this->year),
            //new RawQuotasImport($this->year, $this->user->id, $this->company->id, $this->uuid),
            new VariableImport($this->year),
            $this->file
        )
        ->onQueue('cuotas')
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
