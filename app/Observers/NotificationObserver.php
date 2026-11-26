<?php

namespace App\Observers;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        if ($notification->status !== Notification::STATUS_PENDING) {
            return;
        }

        if ($notification->channel === Notification::CHANNEL_WHATSAPP) {
            if (config('waha.sync', true)) {
                try {
                    SendWhatsAppNotification::dispatchSync($notification);
                } catch (\Throwable $e) {
                    $notification->markFailed($e->getMessage());
                }
            } else {
                SendWhatsAppNotification::dispatch($notification)->onQueue('default');
            }
        } elseif ($notification->channel === Notification::CHANNEL_EMAIL) {
            if (config('mail.sync', true)) {
                try {
                    SendEmailNotification::dispatchSync($notification);
                } catch (\Throwable $e) {
                    $notification->markFailed($e->getMessage());
                }
            } else {
                SendEmailNotification::dispatch($notification)->onQueue('default');
            }
        }
    }
}
