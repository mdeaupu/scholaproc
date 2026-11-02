<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Pesanan - {{ $document->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .letterhead { border-bottom: 2px solid #111; margin-bottom: 20px; padding-bottom: 12px; }
        .letterhead-image { display: block; max-height: 100px; max-width: 100%; margin: 0 auto 8px; }
        .letterhead-identity { text-align: center; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .number { text-align: right; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>SURAT PESANAN</h1>
    <p class="center">Nomor: {{ $document->document_number }}</p>
    <p>Tanggal: {{ $document->document_date?->format('d-m-Y') }}</p>
    <p>
        Pihak Penyedia: {{ $subsidiary['name'] }} · NPWP {{ $subsidiary['npwp'] }} · Direktur {{ $subsidiary['director_name'] }}<br>
        Pihak Sekolah: {{ data_get($snapshot, 'school.name', '-') }} · NPSN {{ data_get($snapshot, 'school.npsn', '-') }}<br>
        Paket: {{ data_get($snapshot, 'transaction.package_category', '-') }}<br>
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }}
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
    <p class="number">Subtotal: Rp {{ number_format((float) data_get($snapshot, 'transaction.subtotal', 0), 2, ',', '.') }}</p>
    @if (data_get($snapshot, 'transaction.tax.validated'))
        <p class="number">Pajak: Rp {{ number_format((float) data_get($snapshot, 'transaction.tax_amount', 0), 2, ',', '.') }}</p>
        <p class="number"><strong>Total: Rp {{ number_format((float) data_get($snapshot, 'transaction.grand_total', 0), 2, ',', '.') }}</strong></p>
    @else
        <p>Perhitungan pajak belum tervalidasi.</p>
    @endif
</body>
</html>
