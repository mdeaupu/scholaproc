<div>
    <div class="py-1 space-y-6">

            {{-- Sapaan & tanggal --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">Selamat datang, {{ $schoolName }}</h1>
                    <p class="text-sm text-gray-500 mt-1">Berikut adalah ringkasan pengadaan barang di sekolah Anda.</p>
                </div>
                <p class="text-sm text-gray-500 flex items-center gap-2 mt-1">
                    <x-mary-icon name="o-calendar-days" class="w-4 h-4 text-[#0046FF]" />
                    {{ $todayLabel }}
                </p>
            </div>
            {{-- Filter tanggal --}}
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="filter-date-from" class="block text-xs font-medium text-gray-500 mb-1">Dari
                            Tanggal Pengajuan</label>
                        <input id="filter-date-from" type="date" wire:model.live="dateFrom"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#0046FF] focus:ring-[#0046FF]">
                    </div>
                    <div>
                        <label for="filter-date-to" class="block text-xs font-medium text-gray-500 mb-1">Sampai
                            Tanggal Pengajuan</label>
                        <input id="filter-date-to" type="date" wire:model.live="dateTo"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#0046FF] focus:ring-[#0046FF]">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" wire:click="resetFilters"
                            class="text-sm text-[#0046FF] hover:text-[#0046FF]/80 font-semibold">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            {{-- 5 kartu statistik --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-blue-50 text-[#0046FF]">
                            <x-mary-icon name="o-clipboard-document-list" class="w-6 h-6" />
                        </span>
                        <p class="text-xs sm:text-sm font-medium text-gray-500 leading-tight">Pengajuan Aktif</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $activeCount }}</p>
                    <p class="text-xs text-gray-400 mt-1">dari total {{ $totalRequests }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-amber-50 text-amber-500">
                            <x-mary-icon name="o-arrows-right-left" class="w-6 h-6" />
                        </span>
                        <p class="text-xs sm:text-sm font-medium text-gray-500 leading-tight">Menunggu Negosiasi</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $negotiationWaitingCount }}</p>
                    <p class="text-xs text-gray-400 mt-1">dari total {{ $totalRequests }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-violet-50 text-violet-500">
                            <x-mary-icon name="o-hand-raised" class="w-6 h-6" />
                        </span>
                        <p class="text-xs sm:text-sm font-medium text-gray-500 leading-tight">Menunggu Keputusan
                            Sekolah</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $pendingNegotiationCount }}</p>
                    <p class="text-xs text-gray-400 mt-1">dari total {{ $totalRequests }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-teal-50 text-teal-500">
                            <x-mary-icon name="o-cog-6-tooth" class="w-6 h-6" />
                        </span>
                        <p class="text-xs sm:text-sm font-medium text-gray-500 leading-tight">Dalam Proses</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $inProcessCount }}</p>
                    <p class="text-xs text-gray-400 mt-1">dari total {{ $totalRequests }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-green-50 text-green-600">
                            <x-mary-icon name="o-check-circle" class="w-6 h-6" />
                        </span>
                        <p class="text-xs sm:text-sm font-medium text-gray-500 leading-tight">Selesai</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $completedCount }}</p>
                    <p class="text-xs text-gray-400 mt-1">dari total {{ $totalRequests }}</p>
                </div>
            </div>

            {{-- Tren pengadaan & kategori paket --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3 bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h2 class="font-bold text-gray-900">
                            Tren Pengadaan <span class="text-sm font-normal text-gray-400">(6 Bulan Terakhir)</span>
                        </h2>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#2563EB] inline-block"></span>
                                Jumlah Pengadaan
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-4 h-0.5 bg-[#2563EB] inline-block"></span>
                                Total Nilai (Rp)
                            </span>
                        </div>
                    </div>
                    <div class="h-64 sm:h-80">
                        <x-mary-chart wire:model="trendChart" class="h-64 sm:h-80" />
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <h2 class="font-bold text-gray-900 mb-4">Pengadaan Berdasarkan Kategori Paket</h2>
                    <div class="flex flex-wrap items-center gap-5">
                        <div class="relative w-40 h-40 shrink-0">
                            <x-mary-chart wire:model="categoryChart" class="h-40 w-40" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-xl sm:text-2xl font-extrabold text-gray-900">{{ $categoryGrandTotal }}</span>
                                <span class="text-xs text-gray-400">Pengajuan</span>
                            </div>
                        </div>
                        <ul class="flex-1 space-y-2 min-w-0">
                            @forelse ($categoryBreakdown as $index => $category)
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                        style="background-color: {{ $categoryColors[$index] ?? '#94A3B8' }}"></span>
                                    <span class="text-gray-700 truncate">{{ $category['name'] }}</span>
                                    <span class="ms-auto font-semibold text-gray-900">{{ $category['count'] }}</span>
                                    <span class="text-gray-400 w-12 text-right">{{ $category['percentage'] }}%</span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-400">Belum ada data kategori.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Pengajuan terbaru & notifikasi --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Pengajuan Terbaru</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs text-gray-500">
                                    <th class="px-5 py-3 font-medium">No. Pengajuan</th>
                                    <th class="px-5 py-3 font-medium">Tanggal</th>
                                    <th class="px-5 py-3 font-medium">Kategori Paket</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium">Total Estimasi</th>
                                    <th class="px-5 py-3 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($recentRequests as $request)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 font-mono font-semibold text-[#0046FF]">
                                            #{{ substr((string) $request->uuid, 0, 8) }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                                            {{ $request->created_at->copy()->locale('id')->isoFormat('D MMM YYYY') }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-600">
                                            {{ $request->packageCategory?->name ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span
                                                class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full {{ $request->status_pill_class }}">
                                                {{ $request->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-gray-900 whitespace-nowrap">
                                            Rp {{ number_format($request->total_estimated_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <button disabled
                                                class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-[#0046FF]/30 text-[#0046FF]/50 cursor-not-allowed"
                                                title="Halaman detail pengajuan belum tersedia">
                                                Lihat
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                            Belum ada riwayat pengajuan barang. Mulai lengkapi infrastruktur sekolah
                                            Anda!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="notifikasi-terbaru"
                    class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Notifikasi Terbaru</h2>
                    </div>
                    <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                        @forelse ($recentNotifications as $notification)
                            <li class="px-5 py-3 flex items-start gap-3 hover:bg-gray-50">
                                <span
                                    class="p-2 rounded-full {{ $notification->status === 'failed' ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-[#0046FF]' }} shrink-0">
                                    <x-mary-icon
                                        name="{{ $notification->status === 'failed' ? 'o-exclamation-circle' : 'o-bell-alert' }}"
                                        class="w-4 h-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-800 leading-snug">
                                        {{ \Illuminate\Support\Str::limit((string) $notification->message_content, 90) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ ($notification->sent_at ?? $notification->created_at)?->diffForHumans() }}
                                    </p>
                                </div>
                                <x-mary-icon name="o-chevron-right" class="w-4 h-4 text-gray-300 shrink-0 mt-1" />
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-gray-500 text-sm">
                                Belum ada notifikasi untuk sekolah Anda.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Negosiasi menunggu keputusan & langkah selanjutnya --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Negosiasi Harga yang Menunggu Keputusan Anda</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs text-gray-500">
                                    <th class="px-5 py-3 font-medium">No. Pengajuan</th>
                                    <th class="px-5 py-3 font-medium">Nama Barang</th>
                                    <th class="px-5 py-3 font-medium">Supplier (diwakili Admin)</th>
                                    <th class="px-5 py-3 font-medium">Harga Negosiasi Terakhir</th>
                                    <th class="px-5 py-3 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($pendingNegotiationItems as $negotiation)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 font-mono font-semibold text-[#0046FF]">
                                            #{{ substr((string) ($negotiation->procurementRequest?->uuid ?? ''), 0, 8) }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-700">
                                            {{ $negotiation->procurementRequestItem?->item_name ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-600">
                                            {{ $negotiation->procurementRequest?->supplier?->company_name ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3 font-semibold text-gray-900 whitespace-nowrap">
                                            Rp {{ number_format((float) $negotiation->offered_price, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <button disabled
                                                class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#0046FF]/40 text-white cursor-not-allowed"
                                                title="Halaman negosiasi belum tersedia">
                                                Lihat & Tanggapi
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                                            Tidak ada negosiasi yang menunggu keputusan Anda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-[#0046FF]/5 rounded-xl border border-[#0046FF]/10 p-5">
                    <div class="flex items-start gap-3">
                        <span class="p-2.5 rounded-full bg-[#0046FF]/10 text-[#0046FF] shrink-0">
                            <x-mary-icon name="o-light-bulb" class="w-6 h-6" />
                        </span>
                        <div>
                            <h2 class="font-bold text-gray-900">Langkah Selanjutnya</h2>
                            @if ($pendingNegotiationCount > 0)
                                <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                                    Terdapat {{ $pendingNegotiationCount }} pengajuan yang menunggu keputusan Anda
                                    (terima/tolak hasil negosiasi harga).
                                </p>
                                <button disabled
                                    class="mt-4 px-4 py-2 text-sm font-semibold rounded-lg bg-[#0046FF]/40 text-white cursor-not-allowed"
                                    title="Halaman pengajuan belum tersedia">
                                    Lihat Pengajuan
                                </button>
                            @else
                                <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                                    Semua negosiasi harga sudah Anda putuskan. Pengajuan yang telah disepakati akan
                                    diproses oleh Admin CV.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dokumen terbaru & menunggu pembayaran --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Dokumen Terbaru</h2>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($recentDocuments as $document)
                            <li class="px-5 py-3 flex items-center justify-between gap-4 hover:bg-gray-50">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="p-2 rounded-full bg-gray-50 text-gray-500 shrink-0">
                                        <x-mary-icon name="o-document-text" class="w-4 h-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ ucfirst(str_replace('_', ' ', (string) $document->document_type)) }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Pengajuan
                                            #{{ substr((string) ($document->procurementRequest?->uuid ?? ''), 0, 8) }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                    {{ $document->generated_at?->format('d M Y') }}
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-gray-500 text-sm">
                                Belum ada dokumen yang diterbitkan.
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex items-center gap-4">
                    <span class="p-3 rounded-full bg-amber-50 text-amber-500 shrink-0">
                        <x-mary-icon name="o-credit-card" class="w-7 h-7" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Menunggu Pembayaran</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-1">{{ $paymentPendingCount }}</p>
                        <p class="text-xs text-gray-400 mt-1">pengajuan sedang menunggu pembayaran</p>
                    </div>
                </div>
            </div>
    </div>
</div>
