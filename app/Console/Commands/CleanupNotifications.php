<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=90 : Number of days to keep notifications}';

    protected $description = 'Hapus notifikasi yang lebih tua dari X hari';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = Notification::where('created_at', '<', $cutoff)->delete();

        $this->info("Berhasil menghapus {$deleted} notifikasi yang lebih tua dari {$days} hari.");

        return Command::SUCCESS;
    }
}
