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
            SendWhatsAppNotification::dispatch($notification);
        } elseif ($notification->channel === Notification::CHANNEL_EMAIL) {
            SendEmailNotification::dispatch($notification);
        }
    }
}
