<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function send(string $to, string $subject, string $htmlBody): array
    {
        try {
            Mail::raw($htmlBody, function ($message) use ($to, $subject, $htmlBody) {
                $message->to($to)
                    ->subject($subject)
                    ->html($htmlBody);
            });

            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Email send failed', [
                'to' => $to,
                'subject' => $subject,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function isAvailable(): bool
    {
        return config('mail.default') !== 'log';
    }
}
