<div wire:poll.10s="pollNotifications">
    @if ($waStatus === 'WORKING')
        <div class="alert alert-success mb-4 text-sm">
            <x-mary-icon name="o-check-circle" class="w-5 h-5" />
            <span>
                WhatsApp Terhubung
                @if ($waPhoneName)
                    — <strong>{{ $waPhoneName }}</strong>
                @endif
            </span>
        </div>
    @elseif ($waStatus === 'SCAN_QR_CODE')
        <div class="alert alert-warning mb-4 text-sm">
            <x-mary-icon name="o-qr-code" class="w-5 h-5" />
            <span>WhatsApp Menunggu Scan QR</span>
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('whatsapp.session') }}" class="link link-primary ml-2" wire:navigate>Buka Halaman Session</a>
            @endif
        </div>
    @elseif ($waStatus === 'STOPPED' || $waStatus === 'TIMEOUT')
        <div class="alert alert-error mb-4 text-sm">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
            <span>WhatsApp Terputus. Notifikasi WhatsApp tidak dapat dikirim.</span>
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('whatsapp.session') }}" class="link link-primary ml-2" wire:navigate>Buka Halaman Session</a>
            @else
                <span class="ml-1">Hubungi superadmin untuk memulihkan koneksi.</span>
            @endif
        </div>
    @else
        <div class="alert alert-warning mb-4 text-sm">
            <x-mary-icon name="o-question-mark-circle" class="w-5 h-5" />
            <span>Status WhatsApp tidak diketahui. Pastikan WAHA server aktif.</span>
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('whatsapp.session') }}" class="link link-primary ml-2" wire:navigate>Buka Halaman Session</a>
            @endif
        </div>
    @endif

    <x-mary-header title="Notifikasi" subtitle="Log pengiriman notifikasi WhatsApp dan Email">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <span class="badge badge-info badge-lg">{{ $stats['total'] }} Total</span>
                <span class="badge badge-warning badge-lg">{{ $stats['pending'] }} Pending</span>
                <span class="badge badge-success badge-lg">{{ $stats['sent'] }} Terkirim</span>
                <span class="badge badge-error badge-lg">{{ $stats['failed'] }} Gagal</span>
            </div>
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        <div class="flex flex-wrap gap-3 mb-4">
            <x-mary-input placeholder="Cari notifikasi..." wire:model.live="search" class="w-64" icon="o-magnifying-glass" />

            <select class="select select-bordered w-40" wire:model.live="statusFilter">
                <option value="all">Semua Status</option>
                <option value="pending">Menunggu</option>
                <option value="sent">Terkirim</option>
                <option value="failed">Gagal</option>
            </select>

            <select class="select select-bordered w-40" wire:model.live="channelFilter">
                <option value="all">Semua Channel</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="email">Email</option>
            </select>
        </div>

        <x-mary-table :headers="$headers" :rows="$notifications" with-pagination row-class="fn($row) => $row->read_at === null ? 'bg-amber-50' : ''">
            @scope('cell_id', $notif)
                <span class="text-xs text-gray-500">#{{ $notif->id }}</span>
            @endscope

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

            @scope('cell_recipient', $notif)
                <span class="text-sm font-medium">{{ $notif->recipient }}</span>
                @if ($notif->procurementRequest)
                    <br />
                    <span class="text-xs text-gray-500">Pengadaan #{{ $notif->procurement_request_id }}</span>
                @endif
            @endscope

            @scope('cell_message_content', $notif)
                <div class="max-w-xs truncate text-sm cursor-pointer hover:text-blue-600" title="Klik untuk melihat detail"
                    wire:click="showDetail({{ $notif->id }})">
                    {{ Str::limit($notif->message_content, 60) }}
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
                    @if ($notif->sent_at)
                        <br />
                        <span class="text-xs text-gray-500">{{ $notif->sent_at->format('d M Y H:i') }}</span>
                    @endif
                @else
                    <span class="badge badge-error badge-sm">Gagal</span>
                    @if ($notif->error_message)
                        <br />
                        <span class="text-xs text-red-500" title="{{ $notif->error_message }}">{{ Str::limit($notif->error_message, 40) }}</span>
                    @endif
                @endif
            @endscope

            @scope('cell_created_at', $notif)
                <span class="text-sm">{{ $notif->created_at->format('d M Y H:i') }}</span>
            @endscope

            @scope('cell_actions', $notif)
                <div class="flex space-x-1">
                    <x-mary-button icon="{{ $notif->isRead() ? 'o-eye-slash' : 'o-eye' }}"
                        class="btn-sm btn-ghost {{ $notif->isRead() ? 'text-gray-400' : 'text-amber-600' }}"
                        wire:click="toggleRead({{ $notif->id }})"
                        tooltip="{{ $notif->isRead() ? 'Tandai belum dibaca' : 'Tandai sudah dibaca' }}"
                        spinner="toggleRead" />
                    @if ($notif->status !== 'sent')
                        <x-mary-button icon="o-arrow-path" class="btn-sm btn-ghost text-blue-600"
                            wire:click="resend({{ $notif->id }})"
                            tooltip="Kirim Ulang"
                            spinner="resend" />
                    @endif
                    <x-mary-button icon="o-trash" class="btn-sm btn-ghost text-error"
                        wire:click="openDeleteModal({{ $notif->id }})"
                        tooltip="Hapus" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showDeleteModal" title="Hapus Notifikasi">
        <p>Apakah Anda yakin ingin menghapus notifikasi ini?</p>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Hapus" class="bg-error text-white" wire:click="confirmDelete" spinner="confirmDelete" />
        </x-slot:actions>
    </x-mary-modal>

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

                <div class="flex items-start gap-2">
                    <span class="text-sm font-medium text-gray-500 w-24">Penerima</span>
                    <span class="text-sm">{{ $detailNotification->recipient }}</span>
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

                @if ($detailNotification->sent_at)
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500 w-24">Dikirim</span>
                        <span class="text-sm">{{ $detailNotification->sent_at->format('d M Y H:i:s') }}</span>
                    </div>
                @endif

                @if ($detailNotification->triggeredBy)
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500 w-24">Ditambah oleh</span>
                        <span class="text-sm">{{ $detailNotification->triggeredBy->name }}</span>
                    </div>
                @endif

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

                @if ($detailNotification->error_message)
                    <div class="mt-4">
                        <span class="text-sm font-medium text-red-500 block mb-2">Error</span>
                        <div class="bg-red-50 rounded-lg p-4 text-sm text-red-600">{{ $detailNotification->error_message }}</div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Tutup" @click="$wire.showDetailModal = false" />
        </x-slot:actions>
    </x-mary-modal>
</div>
