<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Institusi: ') . $schoolName }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="filter-date-from" class="block text-xs font-medium text-gray-500 mb-1">Dari
                            Tanggal Pengajuan</label>
                        <input id="filter-date-from" type="date" wire:model.live="dateFrom"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="filter-date-to" class="block text-xs font-medium text-gray-500 mb-1">Sampai
                            Tanggal Pengajuan</label>
                        <input id="filter-date-to" type="date" wire:model.live="dateTo"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" wire:click="resetFilters"
                            class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Dalam Proses</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeCount }}</p>
                    </div>
                    <div class="p-4 bg-indigo-50 rounded-full text-indigo-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Telah Selesai</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $completedCount }}</p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-full text-green-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Negosiasi Menunggu Keputusan</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $pendingNegotiationCount }}</p>
                    </div>
                    <div class="p-4 bg-yellow-50 rounded-full text-yellow-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Menunggu Pembayaran</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $paymentPendingCount }}</p>
                    </div>
                    <div class="p-4 bg-amber-50 rounded-full text-amber-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">5 Pengajuan Terakhir</h3>
                </div>
                <div class="bg-white">
                    <ul class="divide-y divide-gray-200">
                        @forelse ($recentRequests as $request)
                            <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        #{{ substr((string) $request->uuid, 0, 8) }}
                                    </p>
                                    <p class="text-sm text-gray-500">Estimasi Kebutuhan: Rp
                                        {{ number_format($request->total_estimated_amount, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $request->status_pill_class }}">
                                        {{ $request->status_label }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-gray-500">
                                Belum ada riwayat pengajuan barang. Mulai lengkapi infrastruktur sekolah Anda!
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">Dokumen Terbaru</h3>
                    </div>
                    <ul class="divide-y divide-gray-200">
                        @forelse ($recentDocuments as $document)
                            <li class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ ucfirst(str_replace('_', ' ', (string) $document->document_type)) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Pengajuan
                                        #{{ substr((string) ($document->procurementRequest?->uuid ?? ''), 0, 8) }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                    {{ $document->generated_at?->format('d M Y') }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-gray-500">
                                Belum ada dokumen yang diterbitkan.
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">Notifikasi Terbaru</h3>
                    </div>
                    <ul class="divide-y divide-gray-200">
                        @forelse ($recentNotifications as $notification)
                            <li class="px-6 py-3 flex items-start justify-between gap-4 hover:bg-gray-50">
                                <div>
                                    <p class="text-sm text-gray-900">
                                        {{ \Illuminate\Support\Str::limit((string) $notification->message_content, 80) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Saluran: {{ strtoupper($notification->channel) }}
                                        &middot; {{ ucfirst($notification->status) }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                    {{ ($notification->sent_at ?? $notification->created_at)?->diffForHumans() }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-gray-500">
                                Belum ada notifikasi untuk sekolah Anda.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
