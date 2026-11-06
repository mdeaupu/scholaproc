<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Pengajuan - {{ data_get($snapshot, 'document_number', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        .letterhead { border-bottom: 2px solid #111; margin-bottom: 20px; padding-bottom: 12px; }
        .letterhead-image { display: block; max-height: 100px; max-width: 100%; margin: 0 auto 8px; }
        .letterhead-identity { text-align: center; line-height: 1.5; }
        h1, .number { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .number { text-align: right; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>RINGKASAN PENGAJUAN</h1>
    <p class="number">Nomor dokumen: {{ data_get($snapshot, 'document_number', '-') }}</p>
    <p>
        Sekolah: {{ data_get($snapshot, 'school.name', '-') }}<br>
        NPSN: {{ data_get($snapshot, 'school.npsn', '-') }}<br>
        Anak Perusahaan: {{ data_get($snapshot, 'subsidiary.name', '-') }}<br>
        Paket: {{ data_get($snapshot, 'transaction.package_category', '-') }}<br>
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }}<br>
        Status transaksi: {{ data_get($snapshot, 'transaction.status', '-') }}
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
    <p class="number">
        Total pengajuan: Rp {{ number_format((float) data_get($snapshot, 'transaction.grand_total', data_get($snapshot, 'transaction.subtotal', 0)), 2, ',', '.') }}
    </p>
</body>
</html>
