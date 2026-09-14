<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Notification $notification
    ) {
        $this->onQueue('notifications');
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $result = $whatsapp->send(
            $this->notification->recipient,
            $this->notification->message_content
        );

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
