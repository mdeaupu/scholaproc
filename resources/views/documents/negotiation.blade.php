<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dokumen Hasil Negosiasi - {{ data_get($snapshot, 'transaction.package_category', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .number { text-align: right; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>DOKUMEN HASIL NEGOSIASI</h1>
    <p class="center">Nomor: {{ data_get($snapshot, 'document_number', '-') }}</p>
    <p>
        Sekolah: {{ data_get($snapshot, 'school.name', '-') }}<br>
        Alamat sekolah: {{ data_get($snapshot, 'school.address', '-') }}<br>
        Calon penyedia: {{ data_get($snapshot, 'subsidiary.name', '-') }}<br>
        Alamat penyedia: {{ data_get($snapshot, 'subsidiary.address', '-') }}<br>
        Paket: {{ data_get($snapshot, 'transaction.package_category', '-') }}<br>
        Target tanggal penerimaan: {{ data_get($snapshot, 'transaction.target_receipt_date_numeric', '-') }}
        ({{ data_get($snapshot, 'transaction.target_receipt_date_words', '-') }})
    </p>
    <table>
        <thead>
            <tr>
                <th>No.</th><th>Nama Produk</th><th>Jumlah</th><th>Harga Penawaran</th>
                <th>Harga Negosiasi</th><th>Jumlah Penawaran</th><th>Jumlah Negosiasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'transaction.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['item_name'] ?? '-' }}</td>
                    <td>{{ $item['quantity'] ?? 0 }} {{ $item['unit'] ?? '' }}</td>
                    <td class="number">Rp {{ number_format((float) ($item['offered_price'] ?? 0), 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) ($item['negotiated_price'] ?? 0), 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) ($item['offered_amount'] ?? 0), 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) ($item['negotiated_amount'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="number">
        Total penawaran: Rp {{ number_format((float) data_get($snapshot, 'transaction.offer_subtotal', 0), 0, ',', '.') }}<br>
        Total negosiasi: Rp {{ number_format((float) data_get($snapshot, 'transaction.negotiated_subtotal', 0), 0, ',', '.') }}
    </p>
    <p>
        Kepala Sekolah: {{ data_get($snapshot, 'school.officials.headmaster.name', '-') }}<br>
        Direktur: {{ data_get($snapshot, 'subsidiary.director_name', '-') }}
    </p>
</body>
</html>
