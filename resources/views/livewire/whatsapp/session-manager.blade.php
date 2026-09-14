<div wire:poll.5s="pollStatus">
    <x-mary-header title="WhatsApp Session" subtitle="Kelola koneksi WhatsApp untuk notifikasi">
        <x-slot:actions>
            <x-mary-button icon="o-arrow-path" label="Refresh" wire:click="refreshStatus" spinner="refreshStatus" class="btn-outline" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Status Card -->
        <x-mary-card class="lg:col-span-1">
            <h3 class="text-lg font-bold text-black mb-4">Status Koneksi</h3>

            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">Status</span>
                    <div class="mt-1">
                        <span class="badge badge-lg {{ $this->getStatusBadge() }}">
                            {{ $this->getStatusLabel() }}
                        </span>
                    </div>
                </div>

                @if ($phoneName)
                    <div>
                        <span class="text-sm text-gray-500">Terhubung ke</span>
                        <p class="font-semibold text-black">{{ $phoneName }}</p>
                    </div>
                @endif

                @if ($engineInfo)
                    <div>
                        <span class="text-sm text-gray-500">Engine</span>
                        <p class="text-sm text-gray-700">{{ $engineInfo }}</p>
                    </div>
                @endif

                <div>
                    <span class="text-sm text-gray-500">Session Name</span>
                    <p class="text-sm text-gray-700">default</p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="flex flex-col gap-2">
                @if ($status === 'STOPPED' || $status === 'UNAVAILABLE')
                    <x-mary-button icon="o-play" label="Mulai Session" class="btn-success text-white"
                        wire:click="startSession" spinner="startSession" full />
                @elseif ($status === 'WORKING')
                    <x-mary-button icon="o-arrow-right-start-on-rectangle" label="Ganti Nomor" class="btn-warning text-white"
                        wire:click="openChangeNumberModal" full />
                    <x-mary-button icon="o-stop" label="Hentikan Session" class="btn-error text-white"
                        wire:click="stopSession" spinner="stopSession" full />
                @elseif ($status === 'SCAN_QR_CODE')
                    <x-mary-button icon="o-stop" label="Hentikan Session" class="btn-error text-white"
                        wire:click="stopSession" spinner="stopSession" full />
                @elseif ($status === 'STARTING')
                    <x-mary-button label="Memulai..." class="btn-disabled" disabled full />
                @endif
            </div>
        </x-mary-card>

        <!-- QR Code Card -->
        <x-mary-card class="lg:col-span-2">
            <h3 class="text-lg font-bold text-black mb-4">QR Code WhatsApp</h3>

            @if ($status === 'WORKING')
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="p-6 bg-success/10 rounded-full mb-4">
                        <x-mary-icon name="o-check-circle" class="w-16 h-16 text-success" />
                    </div>
                    <h4 class="text-xl font-bold text-success mb-2">WhatsApp Terhubung!</h4>
                    <p class="text-gray-500 text-center">
                        @if ($phoneName)
                            Terhubung ke <strong>{{ $phoneName }}</strong>
                        @else
                            WhatsApp sudah terhubung dan siap mengirim notifikasi.
                        @endif
                    </p>
                </div>
            @elseif ($status === 'SCAN_QR_CODE' && $qrCode)
                <div class="flex flex-col items-center justify-center py-8">
                    <p class="text-sm text-gray-500 mb-4 text-center">
                        Buka WhatsApp di HP → <strong>Settings → Linked Devices → Link a Device</strong>
                    </p>

                    <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-200">
                        <img src="{{ $qrCode }}" alt="QR Code WhatsApp" class="w-64 h-64" />
                    </div>

                    <div class="mt-4 flex items-center gap-2 text-sm text-gray-500">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4" />
                        QR code akan otomatis refresh. Scan sebelum expired.
                    </div>

                    @if ($isWaitingForScan)
                        <div class="mt-4 flex items-center gap-2 text-sm text-primary">
                            <span class="loading loading-spinner loading-sm"></span>
                            <span>Menunggu scan... HP akan terhubung otomatis.</span>
                        </div>
                    @endif

                    <x-mary-button icon="o-arrow-path" label="Muat Ulang QR" class="btn-outline mt-4"
                        wire:click="loadQR" spinner="loadQR" />
                </div>
            @elseif ($status === 'STARTING')
                <div class="flex flex-col items-center justify-center py-16">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="text-gray-500 mt-4">Memulai session WhatsApp...</p>
                </div>
            @elseif ($status === 'STOPPED')
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="p-6 bg-error/10 rounded-full mb-4">
                        <x-mary-icon name="o-stop-circle" class="w-16 h-16 text-error" />
                    </div>
                    <h4 class="text-xl font-bold text-error mb-2">Session Terhenti</h4>
                    <p class="text-gray-500 text-center">Klik "Mulai Session" untuk memulai koneksi WhatsApp.</p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="p-6 bg-gray-100 rounded-full mb-4">
                        <x-mary-icon name="o-question-mark-circle" class="w-16 h-16 text-gray-400" />
                    </div>
                    <h4 class="text-xl font-bold text-gray-500 mb-2">Status Tidak Diketahui</h4>
                    <p class="text-gray-500 text-center">
                        WAHA server mungkin tidak berjalan. Pastikan Docker container aktif.
                    </p>
                    <x-mary-button icon="o-arrow-path" label="Coba Lagi" class="btn-outline mt-4"
                        wire:click="refreshStatus" spinner="refreshStatus" />
                </div>
            @endif
        </x-mary-card>
    </div>

    <x-mary-modal wire:model="showChangeNumberModal" title="Ganti Nomor WhatsApp">
        <div class="space-y-3">
            <p>Anda akan logout dari WhatsApp saat ini.</p>
            <p>Setelah logout, QR code baru akan muncul. Scan dengan nomor HP yang berbeda.</p>
            <div class="bg-warning/10 border border-warning/30 rounded-lg p-3 text-sm">
                <strong>Perhatian:</strong> Notifikasi yang sedang dalam antrian pengiriman akan gagal jika nomor WhatsApp berubah.
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showChangeNumberModal = false" />
            <x-mary-button label="Ganti Nomor" class="bg-warning text-white" wire:click="logoutSession" spinner="logoutSession" />
        </x-slot:actions>
    </x-mary-modal>
</div>
