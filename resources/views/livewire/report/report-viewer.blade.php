<div class="space-y-6">
    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Laporan Pengadaan</h1>
            <p class="text-sm text-slate-500 mt-1">
                Lihat dan unduh laporan terkait pengadaan barang sekolah ·
                <span class="font-medium text-slate-700">{{ $this->periodLabel() }}</span>
                ({{ number_format($summary['total_transactions'], 0, ',', '.') }} transaksi)
            </p>
        </div>
    </div>

    {{-- Bar Filter Horizontal & Tombol Unduh Laporan --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <div class="flex flex-col xl:flex-row xl:items-end gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 flex-1">
                <x-mary-select wire:model.live="period" :options="$periodPresets" label="Periode" class="select-sm bg-white" />

                @if ($period === \App\Support\ReportFilter::CUSTOM)
                    <x-mary-input type="date" wire:model.live="date_from" label="Dari Tanggal"
                        class="input-sm bg-white" />
                    <x-mary-input type="date" wire:model.live="date_to" label="Sampai Tanggal"
                        class="input-sm bg-white" />
                @endif

                <x-mary-select wire:model.live="school_id" :options="$schools" label="Sekolah"
                    placeholder="Semua Sekolah" class="select-sm bg-white" />
                <x-mary-select wire:model.live="subsidiary_id" :options="$subsidiaries" label="Anak Perusahaan"
                    placeholder="Semua Anak Perusahaan" class="select-sm bg-white" />
                <x-mary-select wire:model.live="funding_source_id" :options="$fundingSources" label="Sumber Dana"
                    placeholder="Semua Sumber Dana" class="select-sm bg-white" />
                <x-mary-select wire:model.live="budget_year_id" :options="$budgetYears" label="Tahun Anggaran"
                    placeholder="Semua Tahun Anggaran" class="select-sm bg-white" />
                <x-mary-select wire:model.live="status" :options="$statusLabels" label="Status"
                    placeholder="Semua Status" class="select-sm bg-white" />
            </div>

            {{-- Aksi Reset & Dropdown Unduh Laporan --}}
            <div class="flex items-center justify-end gap-2 shrink-0 pt-1 xl:pt-0">
                <x-mary-button icon="o-arrow-path" class="btn-ghost btn-sm text-slate-600 hover:text-slate-900"
                    label="Reset Filter" wire:click="resetFilters" />

                <x-mary-dropdown right>
                    <x-slot:trigger>
                        <button type="button"
                            class="btn btn-sm h-10 px-4 bg-[#0046FF] hover:bg-[#0036CC] border-0 text-white font-semibold rounded-xl shadow-sm inline-flex items-center gap-2">
                            <x-mary-icon name="o-chart-bar" class="w-4 h-4" />
                            <span>Unduh Laporan</span>
                            <x-mary-icon name="o-chevron-down" class="w-4 h-4 opacity-80" />
                        </button>
                    </x-slot:trigger>

                    <li>
                        <a href="{{ $this->exportUrl('excel') }}"
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 rounded-lg">
                            <x-mary-icon name="o-document" class="w-4 h-4" />
                            <span>Unduh Excel (.xlsx)</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ $this->exportUrl('pdf') }}"
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 rounded-lg">
                            <x-mary-icon name="o-document-text" class="w-4 h-4" />
                            <span>Unduh PDF (.pdf)</span>
                        </a>
                    </li>
                </x-mary-dropdown>
            </div>
        </div>
    </div>

    {{-- 4 Kartu Ringkasan KPI (Gaya Template: Ikon Pastel di Kiri + Angka Utama) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {{-- Kartu 1: Total Transaksi --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0046FF] flex items-center justify-center shrink-0">
                <x-mary-icon name="o-document-text" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Total Transaksi</p>
                <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5">
                    {{ number_format($summary['total_transactions'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-emerald-600 font-medium mt-1 truncate">
                    {{ number_format($summary['total_item_types'], 0, ',', '.') }} jenis item ·
                    {{ number_format($summary['total_quantity'], 0, ',', '.') }} unit
                </p>
            </div>
        </div>

        {{-- Kartu 2: Total Nilai Resmi --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <x-mary-icon name="o-shopping-cart" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Total Resmi</p>
                <p class="text-xl font-extrabold text-slate-900 tracking-tight mt-0.5 truncate">
                    Rp {{ number_format($summary['total_official'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-slate-500 font-medium mt-1 truncate">
                    Estimasi Rp {{ number_format($summary['total_estimated'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Kartu 3: Selesai & Penghematan --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <x-mary-icon name="o-check-circle" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Transaksi Selesai</p>
                <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5">
                    {{ number_format($summary['completed_count'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-emerald-600 font-medium mt-1 truncate">
                    Penghematan Rp {{ number_format($summary['saving_amount'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Kartu 4: Transaksi Ditolak --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                <x-mary-icon name="o-x-circle" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Transaksi Ditolak</p>
                <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5">
                    {{ number_format($summary['rejected_count'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-slate-500 font-medium mt-1 truncate">
                    {{ $summary['unsubsidiaried_count'] }} belum ditetapkan anak perusahaan
                </p>
            </div>
        </div>
    </div>

    {{-- Visualisasi Ringkas 2 Kolom (Rekap Anak Perusahaan & Donut Distribusi Status) --}}
    @php
        $totalTx = (int) ($summary['total_transactions'] ?? 0);
        $completedTx = (int) ($summary['completed_count'] ?? 0);
        $rejectedTx = (int) ($summary['rejected_count'] ?? 0);
        $inProgressTx = max(0, $totalTx - $completedTx - $rejectedTx);

        $safeTotal = max(1, $totalTx);
        $circumference = 251.33;
        $completedStroke = ($completedTx / $safeTotal) * $circumference;
        $rejectedStroke = ($rejectedTx / $safeTotal) * $circumference;
        $progressStroke = ($inProgressTx / $safeTotal) * $circumference;

        $completedPct = $totalTx > 0 ? round(($completedTx / $totalTx) * 100, 1) : 0;
        $rejectedPct = $totalTx > 0 ? round(($rejectedTx / $totalTx) * 100, 1) : 0;
        $progressPct = $totalTx > 0 ? max(0, round(100 - $completedPct - $rejectedPct, 1)) : 0;

        $maxSubsidiaryOfficial = max(1, (float) ($bySubsidiary->max('total_official') ?? 1));
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {{-- Kiri: Ringkasan Nilai per Anak Perusahaan --}}
        <div
            class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Nilai Pengadaan per Anak Perusahaan</h2>
                    <p class="text-xs text-slate-500">Perbandingan nilai resmi dan volume transaksi grup</p>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0046FF]"></span> Nilai Resmi
                    </span>
                </div>
            </div>

            <div class="space-y-3.5 my-auto">
                @forelse ($bySubsidiary->take(5) as $subRow)
                    @php
                        $barPercent = min(
                            100,
                            round(((float) $subRow['total_official'] / $maxSubsidiaryOfficial) * 100),
                        );
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span
                                class="font-semibold text-slate-800 truncate pr-2">{{ $subRow['subsidiary_name'] }}</span>
                            <span class="text-slate-600 whitespace-nowrap font-medium">
                                {{ number_format($subRow['total_transactions'], 0, ',', '.') }} trx ·
                                <strong class="text-slate-900">Rp
                                    {{ number_format($subRow['total_official'], 0, ',', '.') }}</strong>
                            </span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-[#0046FF] to-sky-400 rounded-full transition-all duration-300"
                                style="width: {{ max($barPercent, $subRow['total_transactions'] > 0 ? 4 : 0) }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-sm text-slate-400 py-8">Belum ada data anak perusahaan pada periode
                        ini.</div>
                @endforelse
            </div>
        </div>

        {{-- Kanan: Donut Chart Distribusi Status Pengadaan --}}
        <div
            class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Distribusi Status Pengadaan</h2>
                <p class="text-xs text-slate-500">Proporsi penyelesaian transaksi pada filter aktif</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-around gap-6 my-4">
                {{-- SVG Donut Chart --}}
                <div class="relative w-36 h-36 shrink-0">
                    <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="#E2E8F0"
                            stroke-width="14" />
                        @if ($totalTx > 0)
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="#0066FF"
                                stroke-width="14" stroke-dasharray="{{ $completedStroke }} {{ $circumference }}"
                                stroke-dashoffset="0" />
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="#EF4444"
                                stroke-width="14" stroke-dasharray="{{ $rejectedStroke }} {{ $circumference }}"
                                stroke-dashoffset="-{{ $completedStroke }}" />
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="#94A3B8"
                                stroke-width="14" stroke-dasharray="{{ $progressStroke }} {{ $circumference }}"
                                stroke-dashoffset="-{{ $completedStroke + $rejectedStroke }}" />
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-xl font-extrabold text-slate-900 leading-none">
                            {{ number_format($totalTx, 0, ',', '.') }}
                        </span>
                        <span class="text-[11px] font-medium text-slate-500 mt-1">Total</span>
                    </div>
                </div>

                {{-- Legenda Persentase --}}
                <div class="space-y-3 w-full sm:w-auto flex-1 max-w-[200px] text-xs">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2 text-slate-600 font-medium">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#0066FF]"></span> Selesai
                        </span>
                        <span class="font-bold text-slate-900">{{ $completedPct }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2 text-slate-600 font-medium">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#EF4444]"></span> Ditolak
                        </span>
                        <span class="font-bold text-slate-900">{{ $rejectedPct }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2 text-slate-600 font-medium">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#94A3B8]"></span> Proses / Lainnya
                        </span>
                        <span class="font-bold text-slate-900">{{ $progressPct }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Area Detail & Rekap Bertab --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <x-mary-tabs wire:model.live="tab">
            {{-- Tab 1: Ringkasan Angka --}}
            <x-mary-tab name="summary" label="Ringkasan" icon="o-chart-bar">
                <div class="pt-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-2 text-sm">
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Jumlah Transaksi</span>
                            <span
                                class="font-semibold text-slate-900">{{ number_format($summary['total_transactions'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Jumlah Item</span>
                            <span
                                class="font-semibold text-slate-900">{{ number_format($summary['total_item_types'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Total Kuantitas</span>
                            <span
                                class="font-semibold text-slate-900">{{ number_format($summary['total_quantity'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Selesai / Ditolak</span>
                            <span
                                class="font-semibold text-slate-900">{{ number_format($summary['completed_count'], 0, ',', '.') }}
                                /
                                {{ number_format($summary['rejected_count'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Total Estimasi</span>
                            <span class="font-semibold text-slate-900">Rp
                                {{ number_format($summary['total_estimated'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Total Resmi</span>
                            <span class="font-semibold text-slate-900">Rp
                                {{ number_format($summary['total_official'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Rata-rata per Transaksi</span>
                            <span class="font-semibold text-slate-900">Rp
                                {{ number_format($summary['average_value'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 py-2.5">
                            <span class="text-slate-500">Penghematan</span>
                            <span class="font-semibold text-emerald-600">Rp
                                {{ number_format($summary['saving_amount'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </x-mary-tab>

            {{-- Tab 2: Daftar Transaksi (Traceable ke Transaksi Sumber) --}}
            <x-mary-tab name="transactions" label="Transaksi ({{ $summary['total_transactions'] }})"
                icon="o-list-bullet">
                <div class="pt-3">
                    <div class="overflow-x-auto rounded-xl border border-slate-200/70">
                        <table class="table table-sm">
                            <thead class="bg-[#F4F7FE] text-slate-700 text-xs">
                                <tr>
                                    <th>No. Pengajuan</th>
                                    <th>Tanggal</th>
                                    <th>Sekolah</th>
                                    <th>Anak Perusahaan</th>
                                    <th>Sumber Dana</th>
                                    <th>Tahun Anggaran</th>
                                    <th>Status</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Estimasi</th>
                                    <th class="text-right">Resmi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($transactions as $transaction)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="font-mono text-xs">
                                            <a href="{{ route('procurement.show', $transaction['uuid']) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                class="text-[#0046FF] font-semibold underline underline-offset-2 hover:no-underline">
                                                {{ $transaction['uuid'] }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap text-slate-600">
                                            {{ \Illuminate\Support\Carbon::parse($transaction['requested_at'] ?? $transaction['created_at'])->translatedFormat('d/m/Y') }}
                                        </td>
                                        <td>
                                            <span
                                                class="font-medium text-slate-900">{{ $transaction['school_name'] }}</span>
                                            <span
                                                class="text-xs text-slate-400 block">{{ $transaction['school_npsn'] }}</span>
                                        </td>
                                        <td class="text-slate-700">{{ $transaction['subsidiary_name'] }}</td>
                                        <td class="text-slate-600">{{ $transaction['funding_source_name'] }}</td>
                                        <td class="text-slate-600">{{ $transaction['budget_year_name'] }}</td>
                                        <td>
                                            <x-mary-badge :value="$transaction['status_label']" class="badge-ghost text-xs font-medium" />
                                        </td>
                                        <td class="text-right text-slate-700">
                                            {{ number_format($transaction['total_quantity'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap text-slate-600">
                                            Rp {{ number_format($transaction['total_estimated'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-right whitespace-nowrap font-bold text-slate-900">
                                            Rp {{ number_format($transaction['total_official'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-slate-400 py-8">Tidak ada transaksi
                                            yang cocok filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($summary['total_transactions'] > \App\Services\ReportExportService::TRANSACTION_LIMIT)
                        <p class="text-xs text-slate-500 mt-3">
                            Preview menampilkan seluruh
                            {{ number_format($summary['total_transactions'], 0, ',', '.') }}
                            transaksi ({{ $transactions->count() }} per halaman). Export Excel/PDF membatasi rincian
                            maksimal
                            {{ number_format(\App\Services\ReportExportService::TRANSACTION_LIMIT, 0, ',', '.') }}
                            baris — angka ringkasan & rekap tetap dihitung dari seluruh data.
                        </p>
                    @endif

                    <div class="mt-4">{{ $transactions->links() }}</div>
                </div>
            </x-mary-tab>

            {{-- Tab 3: Rekap per Sekolah --}}
            <x-mary-tab name="schools" label="Per Sekolah ({{ $bySchool->count() }})" icon="o-building-office-2">
                <div class="pt-3">
                    <div class="overflow-x-auto rounded-xl border border-slate-200/70">
                        <table class="table table-sm">
                            <thead class="bg-[#F4F7FE] text-slate-700 text-xs">
                                <tr>
                                    <th>Sekolah</th>
                                    <th>NPSN</th>
                                    <th class="text-right">Transaksi</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Estimasi</th>
                                    <th class="text-right">Resmi</th>
                                    <th class="text-right">Selesai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($bySchool as $row)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="font-medium text-slate-900">{{ $row['school_name'] }}</td>
                                        <td class="font-mono text-xs text-slate-500">{{ $row['school_npsn'] }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap">Rp
                                            {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap font-bold text-slate-900">Rp
                                            {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['completed_count'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-slate-400 py-8">Tidak ada data
                                            sekolah.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-mary-tab>

            {{-- Tab 4: Rekap per Anak Perusahaan --}}
            <x-mary-tab name="subsidiaries" label="Per Anak Perusahaan ({{ $bySubsidiary->count() }})"
                icon="o-truck">
                <div class="pt-3">
                    <div class="overflow-x-auto rounded-xl border border-slate-200/70">
                        <table class="table table-sm">
                            <thead class="bg-[#F4F7FE] text-slate-700 text-xs">
                                <tr>
                                    <th>Anak Perusahaan</th>
                                    <th class="text-right">Transaksi</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Estimasi</th>
                                    <th class="text-right">Resmi</th>
                                    <th class="text-right">Selesai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($bySubsidiary as $row)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="font-medium text-slate-900">{{ $row['subsidiary_name'] }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap">Rp
                                            {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap font-bold text-slate-900">Rp
                                            {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['completed_count'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-slate-400 py-8">Tidak ada data anak
                                            perusahaan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-mary-tab>

            {{-- Tab 5: Rekap per Status --}}
            <x-mary-tab name="statuses" label="Per Status" icon="o-flag">
                <div class="pt-3">
                    <div class="overflow-x-auto rounded-xl border border-slate-200/70">
                        <table class="table table-sm">
                            <thead class="bg-[#F4F7FE] text-slate-700 text-xs">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-right">Transaksi</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Estimasi</th>
                                    <th class="text-right">Resmi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($byStatus as $row)
                                    <tr
                                        class="{{ $row['total_transactions'] === 0 ? 'text-slate-400' : 'hover:bg-slate-50/80' }}">
                                        <td class="font-medium">{{ $row['label'] }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap">Rp
                                            {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                                        <td class="text-right whitespace-nowrap font-bold">Rp
                                            {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-mary-tab>
        </x-mary-tabs>
    </div>
</div>
