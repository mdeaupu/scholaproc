<h3 class="section-title">1. Ringkasan</h3>

<table class="report-table">
    <tbody>
        <tr>
            <td style="width: 50%">Jumlah Transaksi</td>
            <td class="num">{{ number_format($summary['total_transactions'], 0, ',', '.') }}</td>
            <td style="width: 50%">Total Estimasi</td>
            <td class="num">Rp {{ number_format($summary['total_estimated'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Jumlah Jenis Item</td>
            <td class="num">{{ number_format($summary['total_item_types'], 0, ',', '.') }}</td>
            <td>Total Resmi</td>
            <td class="num">Rp {{ number_format($summary['total_official'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Kuantitas</td>
            <td class="num">{{ number_format($summary['total_quantity'], 0, ',', '.') }}</td>
            <td>Rata-rata per Transaksi</td>
            <td class="num">Rp {{ number_format($summary['average_value'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Transaksi Selesai</td>
            <td class="num">{{ number_format($summary['completed_count'], 0, ',', '.') }}</td>
            <td>Penghematan</td>
            <td class="num">Rp {{ number_format($summary['saving_amount'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Transaksi Ditolak</td>
            <td class="num">{{ number_format($summary['rejected_count'], 0, ',', '.') }}</td>
            <td>Snapshot Terkunci</td>
            <td class="num">{{ number_format($summary['locked_count'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Belum Ada Supplier</td>
            <td class="num">{{ number_format($summary['unsuppliered_count'], 0, ',', '.') }}</td>
            <td class="muted">&nbsp;</td>
            <td class="num muted">&nbsp;</td>
        </tr>
    </tbody>
</table>

<div class="spacer"></div>
