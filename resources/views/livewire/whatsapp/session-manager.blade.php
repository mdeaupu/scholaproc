<div wire:poll.5s="pollStatus" class="space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl sm:text-[26px] font-bold tracking-tight text-slate-900">
            Sesi WhatsApp
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Hubungkan satu nomor WhatsApp resmi untuk mengirim notifikasi pengadaan otomatis.
        </p>
    </div>

    {{-- Main Asymmetric Grid (5 : 7) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Left Column: Scan QR Code Card --}}
        <div
            class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col justify-between">
            <div>
                {{-- Card Header --}}
                <div class="flex items-start gap-3.5">
                    <div
                        class="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M12.031 2c-5.516 0-9.969 4.453-9.969 9.969 0 1.758.459 3.473 1.332 4.986L2 22l5.184-1.359a9.927 9.927 0 0 0 4.847 1.266h.004c5.515 0 9.969-4.454 9.969-9.969A9.93 9.93 0 0 0 19.08 4.92 9.903 9.903 0 0 0 12.031 2Zm5.816 14.27c-.246.693-1.43 1.326-1.984 1.408-.508.076-1.15.108-1.855-.117-.428-.136-.977-.318-1.682-.622-2.961-1.278-4.895-4.262-5.043-4.459-.148-.197-1.205-1.603-1.205-3.058 0-1.455.762-2.17 1.033-2.466.271-.296.591-.37.788-.37.197 0 .394.002.567.01.182.009.426-.069.666.508.246.591.838 2.046.912 2.194.074.148.123.32.025.517-.099.197-.148.32-.296.493-.148.172-.311.385-.444.517-.148.148-.302.308-.13.604.172.296.766 1.263 1.644 2.046 1.13 1.007 2.082 1.32 2.378 1.468.296.148.468.123.64-.074.173-.197.739-.862.936-1.158.197-.296.394-.246.665-.148.271.099 1.724.813 2.02.961.296.148.493.222.567.345.074.123.074.714-.172 1.407Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Tautkan Perangkat</h2>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                            Scan QR Code lewat menu <strong>Perangkat Tertaut</strong> di aplikasi WhatsApp Anda.
                        </p>
                    </div>
                </div>

                {{-- QR / Status Viewport --}}
                <div class="my-6 flex flex-col items-center justify-center min-h-[240px]">
                    @if ($status === 'SCAN_QR_CODE' && $qrCode)
                        <div class="p-3 bg-white rounded-xl border border-slate-100 shadow-2xs">
                            <img src="{{ $qrCode }}" alt="QR Code WhatsApp"
                                class="w-56 h-56 sm:w-60 sm:h-60 object-contain" />
                        </div>

                        @if ($isWaitingForScan)
                            <div class="mt-3 flex items-center gap-2 text-xs text-blue-600 font-medium">
                                <span class="loading loading-spinner loading-xs"></span>
                                <span>Menunggu pemindaian dari HP...</span>
                            </div>
                        @endif

                        <button type="button" wire:click="loadQR" wire:loading.attr="disabled" wire:target="loadQR"
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition cursor-pointer">
                            <x-mary-icon name="o-arrow-path" class="w-3.5 h-3.5" wire:loading.class="animate-spin"
                                wire:target="loadQR" />
                            <span>Muat Ulang QR Code</span>
                        </button>
                    @elseif ($status === 'WORKING')
                        <div class="flex flex-col items-center text-center px-4 py-6">
                            <div
                                class="w-16 h-16 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-3">
                                <x-mary-icon name="o-check-circle" class="w-9 h-9 text-emerald-500" />
                            </div>
                            <p class="text-sm font-semibold text-slate-900">WhatsApp Sudah Terhubung</p>
                            <p class="text-xs text-slate-500 mt-1 max-w-xs leading-relaxed">
                                QR Code tidak diperlukan selama sesi masih aktif. Putuskan sesi terlebih dahulu jika
                                ingin mengganti nomor.
                            </p>
                        </div>
                    @elseif ($status === 'STARTING')
                        <div class="flex flex-col items-center text-center py-8">
                            <span class="loading loading-spinner loading-lg text-blue-600"></span>
                            <p class="text-sm font-medium text-slate-700 mt-3">Menyiapkan QR Code...</p>
                            <p class="text-xs text-slate-400 mt-1">Mohon tunggu beberapa detik</p>
                        </div>
                    @else
                        <div class="flex flex-col items-center text-center px-4 py-6">
                            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                                <x-mary-icon name="o-qr-code" class="w-7 h-7 text-slate-400" />
                            </div>
                            <p class="text-sm font-semibold text-slate-800">QR Code Belum Tersedia</p>
                            <p class="text-xs text-slate-500 mt-1 max-w-xs leading-relaxed">
                                Mulai sesi terlebih dahulu untuk memunculkan kode QR pemindaian.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bottom Info Banner --}}
            <div class="bg-blue-50/90 border border-blue-100 rounded-xl p-3.5 flex items-start gap-3">
                <x-mary-icon name="s-information-circle" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <p class="text-xs sm:text-[13px] text-blue-900 leading-snug">
                    Pastikan Anda sudah login ke akun WhatsApp di ponsel sebelum memindai QR Code.
                </p>
            </div>
        </div>

        {{-- Right Column: Status Koneksi + Informasi + Uji Kirim --}}
        <div class="lg:col-span-7 space-y-6">
            {{-- Card 1: Status Koneksi (Single Session) --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-2.5">
                        <x-mary-icon name="o-device-phone-mobile" class="w-5 h-5 text-blue-900" />
                        <h2 class="text-base font-bold text-slate-900">Status Koneksi</h2>
                    </div>

                    <button type="button" wire:click="refreshStatus" wire:loading.attr="disabled"
                        wire:target="refreshStatus"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-medium transition cursor-pointer">
                        <x-mary-icon name="o-arrow-path" class="w-3.5 h-3.5" wire:loading.class="animate-spin"
                            wire:target="refreshStatus" />
                        <span>Refresh</span>
                    </button>
                </div>

                @if ($status === 'WORKING')
                    @php
                        $connectedSince =
                            property_exists($this, 'connectedAt') && $this->connectedAt
                                ? $this->connectedAt
                                : now()->format('d M Y H:i');
                    @endphp
                    <div
                        class="rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div
                                class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <x-mary-icon name="o-signal" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-base font-bold text-slate-900">
                                        {{ $phoneName ?: 'Nomor Terhubung' }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Terhubung sejak <span
                                        class="font-medium text-slate-700">{{ $connectedSince }}</span>
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="openChangeNumberModal"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-600 text-xs font-semibold transition cursor-pointer shrink-0">
                            <x-mary-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" />
                            <span>Putuskan Koneksi</span>
                        </button>
                    </div>
                @elseif ($status === 'SCAN_QR_CODE' || $status === 'STARTING')
                    <div
                        class="rounded-xl border border-amber-200/80 bg-amber-50/50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div
                                class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <x-mary-icon name="o-qr-code" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">
                                        {{ $status === 'SCAN_QR_CODE' ? 'Menunggu Pemindaian QR' : 'Memulai Sesi...' }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        {{ $this->getStatusLabel() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 mt-1">
                                    Silakan pindai QR Code di panel sebelah kiri untuk menyelesaikan koneksi.
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="stopSession" wire:loading.attr="disabled"
                            wire:target="stopSession"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-600 text-xs font-semibold transition cursor-pointer shrink-0">
                            <x-mary-icon name="o-stop" class="w-4 h-4" />
                            <span>Batalkan</span>
                        </button>
                    </div>
                @else
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div
                                class="w-11 h-11 rounded-xl bg-slate-200/70 text-slate-500 flex items-center justify-center shrink-0">
                                <x-mary-icon name="o-signal-slash" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">Belum Ada Nomor Terhubung</span>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200/80 text-slate-600">
                                        {{ $this->getStatusLabel() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Aktifkan sesi untuk mulai menautkan nomor WhatsApp resmi sistem.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" wire:click="startSession" wire:loading.attr="disabled"
                                wire:target="startSession"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-semibold transition cursor-pointer">
                                <x-mary-icon name="o-play" class="w-4 h-4" />
                                <span wire:loading.remove wire:target="startSession">Mulai Sesi</span>
                                <span wire:loading wire:target="startSession">Memulai...</span>
                            </button>
                            <button type="button" wire:click="restartSession" wire:loading.attr="disabled"
                                wire:target="restartSession" title="Mulai Ulang Sesi"
                                class="inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-50 text-slate-600 transition cursor-pointer">
                                <x-mary-icon name="o-arrow-path" class="w-4 h-4" wire:loading.class="animate-spin"
                                    wire:target="restartSession" />
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Card 2: Informasi --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <div class="flex items-center gap-2.5 mb-3.5">
                    <x-mary-icon name="o-information-circle" class="w-5 h-5 text-blue-900" />
                    <h2 class="text-base font-bold text-slate-900">Informasi</h2>
                </div>

                <ul class="space-y-2.5 text-sm text-slate-700">
                    <li class="flex items-start gap-2.5">
                        <x-mary-icon name="s-check-circle" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                        <span>Sistem menggunakan satu nomor WhatsApp terpusat untuk seluruh notifikasi transaksi.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <x-mary-icon name="s-check-circle" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                        <span>Gunakan tombol <strong>Putuskan Koneksi</strong> apabila ingin mengganti nomor
                            pengirim.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <x-mary-icon name="s-check-circle" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                        <span>Pastikan hanya menggunakan nomor WhatsApp resmi operasional grup.</span>
                    </li>
                </ul>
            </div>

            {{-- Card 3: Uji Coba Kirim WhatsApp --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <div class="flex items-center gap-2.5 mb-1">
                    <x-mary-icon name="o-paper-airplane" class="w-5 h-5 text-blue-900" />
                    <h2 class="text-base font-bold text-slate-900">Uji Coba Kirim WhatsApp</h2>
                </div>
                <p class="text-xs text-slate-500 mb-4">
                    Kirim pesan uji coba untuk memastikan notifikasi WhatsApp berjalan lancar.
                </p>

                <form wire:submit="testSend" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nomor Tujuan</label>
                        <input type="text" name="testPhone" wire:model="testPhone" placeholder="081234567890"
                            class="input input-bordered input-sm w-full rounded-lg" />
                    </div>

                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Pesan</label>
                        <input type="text" name="testMessage" wire:model="testMessage"
                            placeholder="Pesan uji coba..." class="input input-bordered input-sm w-full rounded-lg" />
                    </div>

                    <div class="sm:col-span-3">
                        <button type="submit" wire:loading.attr="disabled" wire:target="testSend"
                            class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-semibold transition cursor-pointer">
                            <x-mary-icon name="o-paper-airplane" class="w-3.5 h-3.5" />
                            <span wire:loading.remove wire:target="testSend">Kirim Uji Coba</span>
                            <span wire:loading wire:target="testSend">Mengirim...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Putuskan / Ganti Nomor --}}
    <x-mary-modal wire:model="showChangeNumberModal" title="Putuskan Koneksi WhatsApp">
        <div class="space-y-3 text-sm text-slate-600">
            <p>Anda akan memutuskan koneksi dan logout dari nomor WhatsApp yang sedang aktif.</p>
            <p>Setelah diputuskan, Anda dapat memindai QR Code baru untuk menghubungkan ulang atau mengganti nomor.</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
                <strong>Perhatian:</strong> Notifikasi otomatis tidak akan terkirim selama sesi WhatsApp dalam keadaan
                terputus.
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showChangeNumberModal = false" />
            <x-mary-button label="Ya, Putuskan" class="btn-error text-white" wire:click="logoutSession"
                spinner="logoutSession" />
        </x-slot:actions>
    </x-mary-modal>
</div>
