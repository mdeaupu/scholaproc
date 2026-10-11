<h3 class="section-title">5. Rincian Transaksi</h3>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 3%">No</th>
            <th style="width: 15%">No. Pengajuan</th>
            <th style="width: 8%">Tanggal</th>
            <th style="width: 17%">Sekolah</th>
            <th style="width: 15%">Supplier</th>
            <th style="width: 12%">Status</th>
            <th style="width: 5%">Qty</th>
            <th style="width: 12%">Estimasi</th>
            <th style="width: 13%">Resmi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transactions as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['uuid'] }}</td>
                <td class="center">
                    {{ \Illuminate\Support\Carbon::parse($row['requested_at'] ?? $row['created_at'])->translatedFormat('d/m/Y') }}
                </td>
                <td>{{ $row['school_name'] }}</td>
                <td>{{ $row['supplier_name'] ?? '-' }}</td>
                <td>{{ $row['status_label'] }}</td>
                <td class="num">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="center muted">Tidak ada transaksi pada filter ini.</td>
            </tr>
        @endforelse
    </tbody>
    @if ($transactions->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="6" class="center">TOTAL ({{ $transactions->count() }} baris detail)</td>
                <td class="num">{{ number_format($transactions->sum('total_quantity'), 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($transactions->sum('total_estimated'), 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($transactions->sum('total_official'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    @endif
</table>

@if ($transactions_truncated ?? false)
    <p class="muted" style="margin-top: 4px;">
        Catatan: menampilkan {{ $transactions->count() }} dari {{ $summary['total_transactions'] }}
        transaksi detail (batas {{ number_format(\App\Services\ReportExportService::TRANSACTION_LIMIT, 0, ',', '.') }} baris);
        ringkasan dan rekap di atas mencakup seluruh data.
    </p>
@endif
