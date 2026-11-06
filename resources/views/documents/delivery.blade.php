<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dokumen Pengiriman - {{ data_get($snapshot, 'transaction.package_category', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>DOKUMEN PENGIRIMAN BARANG</h1>
    <p class="center">Nomor: {{ data_get($snapshot, 'document_number', '-') }}</p>
    <p>
        Tanggal pengiriman: {{ data_get($snapshot, 'stage.delivered_at', '-') }}<br>
        Sekolah: {{ data_get($snapshot, 'school.name', '-') }}<br>
        Alamat: {{ data_get($snapshot, 'school.address', '-') }}<br>
        Paket: {{ data_get($snapshot, 'transaction.package_category', '-') }}
    </p>
    <table>
        <thead><tr><th>No.</th><th>Barang/Jasa</th><th>Spesifikasi</th><th>Jumlah Dikirim</th></tr></thead>
        <tbody>
            @foreach (data_get($snapshot, 'stage.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['item'] ?? '-' }}</td>
                    <td>{{ $item['specification'] ?? '-' }}</td>
                    <td>{{ $item['quantity'] ?? '-' }} {{ $item['unit'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Catatan: {{ data_get($snapshot, 'stage.notes', '-') }}</p>
</body>
</html>