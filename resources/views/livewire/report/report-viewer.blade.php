<div>
    <x-mary-header title="Laporan Pengadaan"
        subtitle="{{ $this->periodLabel() }} · {{ $summary['total_transactions'] }} transaksi" separator>
        <x-slot:actions>
            <x-mary-button icon="o-document" class="btn-success btn-outline" label="Excel" tag="a"
                href="{{ $this->exportUrl('excel') }}" />
            <x-mary-button icon="o-document-text" class="btn-error btn-outline" label="PDF" tag="a"
                href="{{ $this->exportUrl('pdf') }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-mary-select wire:model.live="period" :options="$periodPresets" label="Periode" />

            @if ($period === \App\Support\ReportFilter::CUSTOM)
                <x-mary-input type="date" wire:model.live="date_from" label="Dari Tanggal" />
                <x-mary-input type="date" wire:model.live="date_to" label="Sampai Tanggal" />
            @endif

            <x-mary-select wire:model.live="school_id" :options="$schools" label="Semua Sekolah" />
            <x-mary-select wire:model.live="supplier_id" :options="$suppliers" label="Semua Supplier" />

            <x-mary-select wire:model.live="status" :options="$statusLabels" multiple label="Semua Status" />
        </div>

        <div class="flex justify-end mt-4">
            <x-mary-button icon="o-x-mark" class="btn-ghost btn-sm" label="Reset Filter" wire:click="resetFilters" />
        </div>
    </x-mary-card>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-mary-card shadow class="border-t-4 border-[#0046FF]">
            <p class="text-sm text-gray-500 font-medium">Total Transaksi</p>
            <p class="text-3xl font-bold text-black">{{ number_format($summary['total_transactions'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500">{{ number_format($summary['total_item_types'], 0, ',', '.') }} jenis item ·
                {{ number_format($summary['total_quantity'], 0, ',', '.') }} unit</p>
        </x-mary-card>

        <x-mary-card shadow class="border-t-4 border-[#FF8040]">
            <p class="text-sm text-gray-500 font-medium">Total Estimasi</p>
            <p class="text-2xl font-bold text-black">Rp {{ number_format($summary['total_estimated'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500">Rata-rata Rp {{ number_format($summary['average_value'], 0, ',', '.') }} per transaksi
            </p>
        </x-mary-card>

        <x-mary-card shadow class="border-t-4 border-black">
            <p class="text-sm text-gray-500 font-medium">Total Resmi</p>
            <p class="text-2xl font-bold text-black">Rp {{ number_format($summary['total_official'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500">{{ $summary['locked_count'] }} transaksi memakai snapshot terkunci</p>
        </x-mary-card>

        <x-mary-card shadow class="border-t-4 border-success">
            <p class="text-sm text-gray-500 font-medium">Penghematan</p>
            <p class="text-2xl font-bold text-success">Rp {{ number_format($summary['saving_amount'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500">{{ $summary['unsuppliered_count'] }} transaksi belum ada supplier</p>
        </x-mary-card>
    </div>

    <x-mary-tabs wire:model.live="tab">
        <x-mary-tab name="summary" label="Ringkasan" icon="o-chart-bar">
            <x-mary-card shadow title="Rincian Angka">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Jumlah Transaksi</span>
                        <span class="font-semibold">{{ number_format($summary['total_transactions'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Jumlah Item</span>
                        <span class="font-semibold">{{ number_format($summary['total_item_types'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Total Kuantitas</span>
                        <span class="font-semibold">{{ number_format($summary['total_quantity'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Selesai / Ditolak</span>
                        <span class="font-semibold">{{ number_format($summary['completed_count'], 0, ',', '.') }} /
                            {{ number_format($summary['rejected_count'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Total Estimasi</span>
                        <span class="font-semibold">Rp {{ number_format($summary['total_estimated'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Total Resmi</span>
                        <span class="font-semibold">Rp {{ number_format($summary['total_official'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Rata-rata per Transaksi</span>
                        <span class="font-semibold">Rp {{ number_format($summary['average_value'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 py-2">
                        <span class="text-gray-500">Penghematan</span>
                        <span class="font-semibold text-success">Rp {{ number_format($summary['saving_amount'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </x-mary-card>
        </x-mary-tab>

        <x-mary-tab name="transactions" label="Transaksi ({{ $summary['total_transactions'] }})" icon="o-list-bullet">
            <x-mary-card shadow class="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>No. Pengajuan</th>
                                <th>Tanggal</th>
                                <th>Sekolah</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Estimasi</th>
                                <th class="text-right">Resmi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="font-mono text-xs">{{ $transaction['uuid'] }}</td>
                                    <td class="whitespace-nowrap">
                                        {{ \Illuminate\Support\Carbon::parse($transaction['requested_at'] ?? $transaction['created_at'])->translatedFormat('d/m/Y') }}
                                    </td>
                                    <td>
                                        {{ $transaction['school_name'] }}
                                        <span class="text-xs text-gray-400 block">{{ $transaction['school_npsn'] }}</span>
                                    </td>
                                    <td>{{ $transaction['supplier_name'] ?? '-' }}</td>
                                    <td>
                                        <x-mary-badge :value="$transaction['status_label']" class="badge-ghost" />
                                    </td>
                                    <td class="text-right">{{ number_format($transaction['total_quantity'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        Rp {{ number_format($transaction['total_estimated'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-right whitespace-nowrap font-semibold">
                                        Rp {{ number_format($transaction['total_official'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-gray-400 py-6">Tidak ada transaksi yang cocok
                                        filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($summary['total_transactions'] > \App\Services\ReportExportService::TRANSACTION_LIMIT)
                    <p class="text-xs text-gray-500 px-4 py-3 border-t">
                        Preview menampilkan seluruh {{ number_format($summary['total_transactions'], 0, ',', '.') }}
                        transaksi ({{ $transactions->count() }} per halaman). Export Excel/PDF membatasi rincian
                        maksimal {{ number_format(\App\Services\ReportExportService::TRANSACTION_LIMIT, 0, ',', '.') }}
                        baris — angka ringkasan & rekap tetap dihitung dari seluruh data.
                    </p>
                @endif
            </x-mary-card>

            <div class="mt-4">{{ $transactions->links() }}</div>
        </x-mary-tab>

        <x-mary-tab name="schools" label="Per Sekolah ({{ $bySchool->count() }})" icon="o-building-office-2">
            <x-mary-card shadow class="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
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
                        <tbody>
                            @forelse ($bySchool as $row)
                                <tr>
                                    <td>{{ $row['school_name'] }}</td>
                                    <td class="font-mono text-xs">{{ $row['school_npsn'] }}</td>
                                    <td class="text-right">{{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap font-semibold">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row['completed_count'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-gray-400 py-6">Tidak ada data sekolah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </x-mary-tab>

        <x-mary-tab name="suppliers" label="Per Supplier ({{ $bySupplier->count() }})" icon="o-truck">
            <x-mary-card shadow class="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th class="text-right">Transaksi</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Estimasi</th>
                                <th class="text-right">Resmi</th>
                                <th class="text-right">Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bySupplier as $row)
                                <tr>
                                    <td>{{ $row['company_name'] }}</td>
                                    <td class="text-right">{{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap font-semibold">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row['completed_count'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-400 py-6">Tidak ada data supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </x-mary-tab>

        <x-mary-tab name="statuses" label="Per Status" icon="o-flag">
            <x-mary-card shadow class="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-right">Transaksi</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Estimasi</th>
                                <th class="text-right">Resmi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byStatus as $row)
                                <tr class="{{ $row['total_transactions'] === 0 ? 'text-gray-400' : '' }}">
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">{{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                                    <td class="text-right whitespace-nowrap font-semibold">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-mary-card>
        </x-mary-tab>
    </x-mary-tabs>
</div>
