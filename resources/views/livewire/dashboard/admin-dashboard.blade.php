<div>
    <div class="py-1 space-y-6">

            {{-- Sapaan & tanggal --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">Dasbor Operasional CV</h1>
                    <p class="text-sm text-gray-500 mt-1">Ringkasan pengadaan yang perlu ditindaklanjuti oleh tim CV.
                    </p>
                </div>
                <p class="text-sm text-gray-500 flex items-center gap-2 mt-1">
                    <x-mary-icon name="o-calendar-days" class="w-4 h-4 text-[#0046FF]" />
                    {{ \Illuminate\Support\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
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

            {{-- 3 kartu headline --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-amber-50 text-amber-500">
                            <x-mary-icon name="o-shield-check" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Menunggu Verifikasi Anda</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $pendingVerificationCount }}
                        <span class="text-base font-medium text-gray-500">Dokumen</span></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-violet-50 text-violet-500">
                            <x-mary-icon name="o-arrows-right-left" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Negosiasi Menunggu Tanggapan</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $pendingNegotiationCount }}
                        <span class="text-base font-medium text-gray-500">Tanggapan</span></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-blue-50 text-[#0046FF]">
                            <x-mary-icon name="o-cog-6-tooth" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Total Proses Aktif Berjalan</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalActiveProcesses }}
                        <span class="text-base font-medium text-gray-500">Pengadaan</span></p>
                </div>
            </div>

            {{-- Perlu ditindaklanjuti --}}
            <div>
                <h2 class="text-sm font-semibold text-gray-500 mb-3">Perlu Ditindaklanjuti</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <p class="text-xs text-gray-500 font-medium">Diproses</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $processingCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <p class="text-xs text-gray-500 font-medium">Diterima Sebagian</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $partiallyReceivedCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <p class="text-xs text-gray-500 font-medium">Diterima</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $receivedCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <p class="text-xs text-gray-500 font-medium">Ditagih</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $invoicedCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <p class="text-xs text-gray-500 font-medium">Menunggu Pembayaran</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $paymentPendingCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <p class="text-xs text-gray-500 font-medium">Bermasalah</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $disputedCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Antrean verifikasi --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900">Antrean Verifikasi Masuk</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs text-gray-500">
                                <th class="px-5 py-3 font-medium">No. Pengajuan</th>
                                <th class="px-5 py-3 font-medium">Asal Sekolah</th>
                                <th class="px-5 py-3 font-medium">Tanggal Masuk</th>
                                <th class="px-5 py-3 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($pendingVerifications as $request)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 font-mono font-semibold text-[#0046FF]">
                                        #{{ substr((string) $request->uuid, 0, 8) }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">
                                        {{ $request->school?->name ?? 'Sekolah Tidak Ditemukan' }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">
                                        {{ $request->created_at->format('d M Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button disabled
                                            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#0046FF]/40 text-white cursor-not-allowed"
                                            title="Halaman verifikasi belum tersedia">
                                            Verifikasi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-gray-500">
                                        Tarik napas! Tidak ada pengajuan yang menunggu verifikasi saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Negosiasi menunggu tanggapan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900">Negosiasi Menunggu Tanggapan CV</h2>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($pendingNegotiations as $negotiation)
                        <li class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">
                                    #{{ substr((string) ($negotiation->procurementRequest?->uuid ?? ''), 0, 8) }}
                                    <span class="text-sm font-normal text-gray-500">
                                        &middot; {{ $negotiation->procurementRequest?->school?->name ?? 'Sekolah tidak diketahui' }}
                                    </span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Diajukan oleh {{ $negotiation->user?->name ?? 'Pengguna' }} &middot;
                                    Putaran {{ $negotiation->round_number }}
                                </p>
                            </div>
                            <span
                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 whitespace-nowrap">
                                Rp {{ number_format((float) $negotiation->offered_price, 0, ',', '.') }}
                            </span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-gray-500">
                            Tidak ada negosiasi yang menunggu tanggapan CV.
                        </li>
                    @endforelse
                </ul>
            </div>
    </div>
</div>
