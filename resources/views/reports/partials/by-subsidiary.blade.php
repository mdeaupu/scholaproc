<h3 class="section-title">3. Rekap per Anak Perusahaan</h3>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 4%">No</th>
            <th>Anak Perusahaan</th>
            <th style="width: 9%">Transaksi</th>
            <th style="width: 9%">Qty</th>
            <th style="width: 15%">Estimasi</th>
            <th style="width: 15%">Resmi</th>
            <th style="width: 14%">Penghematan</th>
            <th style="width: 9%">Selesai</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($by_subsidiary as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['subsidiary_name'] }}</td>
                <td class="num">{{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_estimated'] - $row['total_official'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($row['completed_count'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="center muted">Tidak ada data anak perusahaan pada filter ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="spacer"></div>
