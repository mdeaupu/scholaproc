<div>
    <x-mary-header title="Notifikasi Saya" subtitle="Notifikasi terkait pengadaan sekolah Anda">
        <x-slot:actions>
            @if ($unreadCount > 0)
                <span class="badge badge-warning badge-lg">{{ $unreadCount }} Belum Dibaca</span>
            @endif
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        <div class="flex flex-wrap gap-3 mb-4">
            <x-mary-input placeholder="Cari notifikasi..." wire:model.live="search" class="w-64" icon="o-magnifying-glass" />

            <select class="select select-bordered w-40" wire:model.live="channelFilter">
                <option value="all">Semua Channel</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="email">Email</option>
            </select>
        </div>

        <x-mary-table :headers="$headers" :rows="$notifications" with-pagination row-class="fn($row) => $row->read_at === null ? 'bg-amber-50' : ''">
            @scope('cell_channel', $notif)
                @if ($notif->channel === 'whatsapp')
                    <span class="badge badge-success badge-sm gap-1">
                        <x-mary-icon name="o-chat-bubble-left" class="w-3 h-3" />
                        WhatsApp
                    </span>
                @else
                    <span class="badge badge-info badge-sm gap-1">
                        <x-mary-icon name="o-envelope" class="w-3 h-3" />
                        Email
                    </span>
                @endif
            @endscope

            @scope('cell_message_content', $notif)
                <div class="max-w-md truncate text-sm cursor-pointer hover:text-blue-600" title="Klik untuk melihat detail"
                    wire:click="showDetail({{ $notif->id }})">
                    @if (!$notif->isRead())
                        <span class="inline-block w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
                    @endif
                    {{ Str::limit($notif->message_content, 80) }}
                </div>
            @endscope

            @scope('cell_status', $notif)
                @if ($notif->status === 'pending')
                    <span class="badge badge-warning badge-sm gap-1">
                        <span class="loading loading-spinner loading-xs"></span>
                        Menunggu
                    </span>
                @elseif ($notif->status === 'sent')
                    <span class="badge badge-success badge-sm">Terkirim</span>
                @else
                    <span class="badge badge-error badge-sm">Gagal</span>
                @endif
            @endscope

            @scope('cell_created_at', $notif)
                <span class="text-sm text-gray-500">{{ $notif->created_at->diffForHumans() }}</span>
            @endscope

            @scope('cell_actions', $notif)
                <div class="flex space-x-1">
                    <x-mary-button icon="{{ $notif->isRead() ? 'o-eye-slash' : 'o-eye' }}"
                        class="btn-sm btn-ghost {{ $notif->isRead() ? 'text-gray-400' : 'text-amber-600' }}"
                        wire:click="toggleRead({{ $notif->id }})"
                        tooltip="{{ $notif->isRead() ? 'Tandai belum dibaca' : 'Tandai sudah dibaca' }}"
                        spinner="toggleRead" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showDetailModal" title="Detail Notifikasi">
        @if ($detailNotification)
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 w-24">Channel</span>
                    @if ($detailNotification->channel === 'whatsapp')
                        <span class="badge badge-success badge-sm gap-1">
                            <x-mary-icon name="o-chat-bubble-left" class="w-3 h-3" />
                            WhatsApp
                        </span>
                    @else
                        <span class="badge badge-info badge-sm gap-1">
                            <x-mary-icon name="o-envelope" class="w-3 h-3" />
                            Email
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 w-24">Status</span>
                    @if ($detailNotification->status === 'pending')
                        <span class="badge badge-warning badge-sm gap-1">
                            <span class="loading loading-spinner loading-xs"></span>
                            Menunggu
                        </span>
                    @elseif ($detailNotification->status === 'sent')
                        <span class="badge badge-success badge-sm">Terkirim</span>
                    @else
                        <span class="badge badge-error badge-sm">Gagal</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 w-24">Waktu</span>
                    <span class="text-sm">{{ $detailNotification->created_at->format('d M Y H:i:s') }}</span>
                </div>

                @if ($detailNotification->procurementRequest)
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500 w-24">Pengadaan</span>
                        <a href="{{ route('procurement.show', $detailNotification->procurement_request_id) }}"
                            class="text-sm text-blue-600 hover:underline" wire:navigate>
                            #{{ $detailNotification->procurement_request_id }}
                        </a>
                    </div>
                @endif

                <div class="mt-4">
                    <span class="text-sm font-medium text-gray-500 block mb-2">Pesan</span>
                    <div class="bg-gray-50 rounded-lg p-4 text-sm whitespace-pre-wrap">{{ $detailNotification->message_content }}</div>
                </div>
            </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Tutup" @click="$wire.showDetailModal = false" />
        </x-slot:actions>
    </x-mary-modal>
</div>
