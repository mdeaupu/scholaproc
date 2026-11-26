<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\ProcurementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcurementNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?Notification $notification,
        public ?ProcurementRequest $procurementRequest,
        public string $subjectLine,
        public string $messageBody,
        public array $documents = []
    ) {}

    public function build(): static
    {
        return $this->subject($this->subjectLine)
            ->markdown('emails.procurement-notification', [
                'notification' => $this->notification,
                'procurementRequest' => $this->procurementRequest,
                'messageBody' => $this->messageBody,
                'documents' => $this->documents,
            ]);
    }
}
