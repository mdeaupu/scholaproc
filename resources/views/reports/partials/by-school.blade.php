<h3 class="section-title">2. Rekap per Sekolah</h3>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 4%">No</th>
            <th>Sekolah</th>
            <th style="width: 10%">NPSN</th>
            <th style="width: 9%">Transaksi</th>
            <th style="width: 9%">Qty</th>
            <th style="width: 14%">Estimasi</th>
            <th style="width: 14%">Resmi</th>
            <th style="width: 13%">Selesai</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($by_school as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['school_name'] }}</td>
                <td>{{ $row['school_npsn'] }}</td>
                <td class="num">{{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($row['completed_count'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="center muted">Tidak ada data sekolah pada filter ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="spacer"></div>
