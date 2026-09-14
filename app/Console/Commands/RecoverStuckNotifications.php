<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Notification;
use Illuminate\Console\Command;

class RecoverStuckNotifications extends Command
{
    protected $signature = 'notifications:recover {--minutes=10 : Consider notifications stuck after this many minutes} {--max-retries=3 : Max retry attempts before marking as failed}';

    protected $description = 'Retry atau force-fail notifikasi yang stuck di status pending';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $maxRetries = (int) $this->option('max-retries');
        $cutoff = now()->subMinutes($minutes);

        $stuckNotifications = Notification::pending()
            ->where('created_at', '<', $cutoff)
            ->get();

        $retried = 0;
        $failed = 0;

        foreach ($stuckNotifications as $notification) {
            $retryCount = $notification->meta['retry_count'] ?? 0;

            if ($retryCount >= $maxRetries) {
                $notification->markFailed('Auto-failed: exceeded max retries after being stuck');
                $failed++;
                continue;
            }

            $notification->update([
                'meta' => array_merge($notification->meta ?? [], ['retry_count' => $retryCount + 1]),
            ]);

            if ($notification->channel === Notification::CHANNEL_WHATSAPP) {
                SendWhatsAppNotification::dispatch($notification);
            } else {
                SendEmailNotification::dispatch($notification);
            }

            $retried++;
        }

        $this->info("Recovery selesai: {$retried} notifikasi diretry, {$failed} notifikasi di-force-fail.");

        return Command::SUCCESS;
    }
}
