<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Notification $notification
    ) {
        $this->onQueue('default');
    }

    public function handle(EmailService $email): void
    {
        $result = $email->send($this->notification);

        if ($result['success']) {
            $this->notification->markSent();
        } else {
            $this->notification->markFailed($result['error'] ?? 'Unknown error');
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->notification->markFailed($exception->getMessage());
    }
}
