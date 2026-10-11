<h3 class="section-title">4. Rekap per Status</h3>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 5%">No</th>
            <th style="width: 28%">Status</th>
            <th style="width: 15%">Kode</th>
            <th style="width: 12%">Transaksi</th>
            <th style="width: 10%">Persentase</th>
            <th style="width: 10%">Qty</th>
            <th style="width: 20%">Estimasi</th>
            <th style="width: 20%">Resmi</th>
        </tr>
    </thead>
    <tbody>
        @php
            $statusTotal = max($summary['total_transactions'], 1);
        @endphp
        @foreach ($by_status as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['label'] }}</td>
                <td class="muted">{{ $row['status'] }}</td>
                <td class="num">{{ number_format($row['total_transactions'], 0, ',', '.') }}</td>
                <td class="num">{{ round($row['total_transactions'] / $statusTotal * 100, 1) }}%</td>
                <td class="num">{{ number_format($row['total_quantity'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_estimated'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($row['total_official'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="spacer"></div>
