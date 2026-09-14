<?php

namespace App\Livewire\Notification;

use App\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class NotificationInbox extends Component
{
    use WithPagination, Toast;

    public string $channelFilter = 'all';
    public string $search = '';

    public bool $showDetailModal = false;
    public ?Notification $detailNotification = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isSchool(), 403);
    }

    public function updatedChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleRead(int $notificationId): void
    {
        $notification = Notification::findOrFail($notificationId);

        $this->authorizeNotification($notification);

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

        $this->authorizeNotification($this->detailNotification);

        if (!$this->detailNotification->isRead()) {
            $this->detailNotification->markAsRead();
        }

        $this->showDetailModal = true;
    }

    public function render()
    {
        $schoolId = auth()->user()->school_id;

        $query = Notification::with('procurementRequest')
            ->whereHas('procurementRequest', fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('event_type', Notification::SCHOOL_EVENTS)
            ->latest();

        if ($this->channelFilter !== 'all') {
            $query->where('channel', $this->channelFilter);
        }

        if ($this->search) {
            $query->where('message_content', 'like', "%{$this->search}%");
        }

        $notifications = $query->paginate(15);

        $unreadCount = Notification::unread()
            ->whereHas('procurementRequest', fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('event_type', Notification::SCHOOL_EVENTS)
            ->count();

        return view('livewire.notification.notification-inbox', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'headers' => [
                ['key' => 'channel', 'label' => 'Channel'],
                ['key' => 'message_content', 'label' => 'Pesan', 'sortable' => false],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'created_at', 'label' => 'Waktu'],
                ['key' => 'actions', 'label' => 'Aksi', 'sortable' => false],
            ],
        ])->layout('layouts.app');
    }

    private function authorizeNotification(Notification $notification): void
    {
        $schoolId = auth()->user()->school_id;

        abort_unless(
            $notification->procurementRequest &&
            $notification->procurementRequest->school_id === $schoolId,
            403
        );
    }
}
