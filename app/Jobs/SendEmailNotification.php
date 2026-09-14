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
        $this->onQueue('notifications');
    }

    public function handle(EmailService $email): void
    {
        $subject = 'Notifikasi Pengadaan — ' . ($this->notification->procurementRequest?->school?->name ?? 'Sistem');

        $result = $email->send(
            $this->notification->recipient,
            $subject,
            $this->buildHtmlBody()
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

    private function buildHtmlBody(): string
    {
        $message = $this->notification->message_content;
        $procurementRequest = $this->notification->procurementRequest;
        $documents = $procurementRequest?->generatedDocuments ?? collect();

        return view('emails.notification', [
            'message' => $message,
            'recipient' => $this->notification->recipient,
            'channel' => $this->notification->channel,
            'procurementRequest' => $procurementRequest,
            'documents' => $documents,
        ])->render();
    }
}
