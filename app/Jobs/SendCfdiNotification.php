<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Notifications\PushNotification;

class SendCfdiNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected $user;
    protected $message;

    public function __construct(User $user, array $message)
    {
        $this->user = $user;
        $this->message = $message;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new PushNotification($this->message['title'], $this->message['content']));
    }
}
