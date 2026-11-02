<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $document->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .letterhead { border-bottom: 2px solid #111; margin-bottom: 20px; padding-bottom: 12px; }
        .letterhead-image { display: block; max-height: 100px; max-width: 100%; margin: 0 auto 8px; }
        .letterhead-identity { text-align: center; line-height: 1.5; }
        h1, .number { text-align: right; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .number { text-align: right; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>INVOICE</h1>
    <p>
        Nomor: {{ data_get($snapshot, 'stage.invoice_number', $document->document_number) }}<br>
        Tanggal: {{ data_get($snapshot, 'stage.invoice_date', $document->document_date?->format('Y-m-d')) }}<br>
        Kepada: {{ data_get($snapshot, 'school.name', '-') }}
    </p>
    <table>
        <thead>
            <tr><th>Barang</th><th>Spesifikasi</th><th>Jumlah</th><th>Harga Satuan</th><th>Jumlah Harga</th></tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'transaction.items', []) as $item)
                <tr>
                    <td>{{ $item['item_name'] ?? '-' }}</td>
                    <td>{{ $item['specification'] ?? '-' }}</td>
                    <td>{{ $item['quantity'] ?? '-' }} {{ $item['unit'] ?? '' }}</td>
                    <td class="number">Rp {{ number_format((float) ($item['official_price'] ?? 0), 2, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) ($item['amount'] ?? 0), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="number">Subtotal: Rp {{ number_format((float) data_get($snapshot, 'stage.subtotal', data_get($snapshot, 'transaction.subtotal', 0)), 2, ',', '.') }}</p>
    @if (data_get($snapshot, 'transaction.tax.validated'))
        <p class="number">Pajak: Rp {{ number_format((float) data_get($snapshot, 'stage.tax_amount', 0), 2, ',', '.') }}</p>
        <p class="number"><strong>Total Tagihan: Rp {{ number_format((float) data_get($snapshot, 'stage.total_amount', 0), 2, ',', '.') }}</strong></p>
    @else
        <p>Konfigurasi pajak belum tervalidasi; nilai final tidak dapat ditampilkan.</p>
    @endif
</body>
</html>
