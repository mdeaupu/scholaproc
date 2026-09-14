<?php

namespace App\Livewire\Notification;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Notification;
use App\Services\WhatsAppService;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class NotificationManager extends Component
{
    use WithPagination, Toast;

    public string $statusFilter = 'all';
    public string $channelFilter = 'all';
    public string $search = '';

    public bool $showDeleteModal = false;
    public ?int $deletingNotificationId = null;

    public bool $showDetailModal = false;
    public ?Notification $detailNotification = null;

    public string $waStatus = 'UNKNOWN';
    public ?string $waPhoneName = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->isAdmin(), 403);

        $this->checkWhatsAppStatus();
    }

    public function checkWhatsAppStatus(): void
    {
        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $info = $wa->getSessionInfo();

        if ($info) {
            $this->waStatus = $info['status'] ?? 'UNKNOWN';
            $this->waPhoneName = $info['me']['pushName'] ?? null;
        } else {
            $this->waStatus = 'UNAVAILABLE';
            $this->waPhoneName = null;
        }
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function pollNotifications(): void
    {
        $this->checkWhatsAppStatus();
        $this->dispatch('$refresh');
    }

    public function resend(int $notificationId): void
    {
        $notification = Notification::findOrFail($notificationId);

        if ($notification->status === Notification::STATUS_SENT) {
            $this->error('Notifikasi sudah terkirim. Tidak perlu dikirim ulang.');

            return;
        }

        $notification->update([
            'status' => Notification::STATUS_PENDING,
            'error_message' => null,
            'sent_at' => null,
        ]);

        if ($notification->channel === Notification::CHANNEL_WHATSAPP) {
            SendWhatsAppNotification::dispatch($notification);
        } else {
            SendEmailNotification::dispatch($notification);
        }

        $this->success('Notifikasi berhasil dikirim ulang.');
    }

    public function openDeleteModal(int $notificationId): void
    {
        $this->deletingNotificationId = $notificationId;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        $notification = Notification::findOrFail($this->deletingNotificationId);
        $notification->delete();

        $this->showDeleteModal = false;
        $this->deletingNotificationId = null;
        $this->success('Notifikasi berhasil dihapus.');
    }

    public function delete(int $notificationId): void
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->delete();
        $this->success('Notifikasi berhasil dihapus.');
    }

    public function toggleRead(int $notificationId): void
    {
        $notification = Notification::findOrFail($notificationId);

        if ($notification->isRead()) {
            $notification->markAsUnread();
            $this->success('Notifikasi ditandai belum dibaca.');
        } else {
            $notification->markAsRead();
            $this->success('Notifikasi ditandai sudah dibaca.');
        }
    }

    public function showDetail(int $notificationId): void
    {
        $this->detailNotification = Notification::with('procurementRequest.school', 'triggeredBy')
            ->findOrFail($notificationId);

        if (!$this->detailNotification->isRead()) {
            $this->detailNotification->markAsRead();
        }

        $this->showDetailModal = true;
    }

    public function render()
    {
        $query = Notification::with('procurementRequest.school')
            ->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->channelFilter !== 'all') {
            $query->where('channel', $this->channelFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('recipient', 'like', "%{$this->search}%")
                    ->orWhere('message_content', 'like', "%{$this->search}%");
            });
        }

        $notifications = $query->paginate(15);

        $stats = [
            'total' => Notification::count(),
            'pending' => Notification::pending()->count(),
            'sent' => Notification::sent()->count(),
            'failed' => Notification::failed()->count(),
        ];

        return view('livewire.notification.notification-manager', [
            'notifications' => $notifications,
            'stats' => $stats,
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'channel', 'label' => 'Channel'],
                ['key' => 'recipient', 'label' => 'Penerima'],
                ['key' => 'message_content', 'label' => 'Pesan', 'sortable' => false],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'created_at', 'label' => 'Waktu'],
                ['key' => 'actions', 'label' => 'Aksi', 'sortable' => false],
            ],
        ])->layout('layouts.app');
    }
}
