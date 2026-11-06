<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Hasil Pemeriksaan - {{ data_get($snapshot, 'transaction.package_category', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    @include('documents.partials.school-letterhead')
    <h1>SURAT HASIL PEMERIKSAAN</h1>
    <p class="center">Nomor: {{ data_get($snapshot, 'document_number', '-') }}</p>
    <p>
        Tanggal pemeriksaan: {{ data_get($snapshot, 'stage.inspection_date_numeric', '-') }}<br>
        Terbilang: {{ data_get($snapshot, 'stage.inspection_date_words', '-') }}
    </p>
    <p>
        Nomor Surat Pesanan: {{ data_get($snapshot, 'stage.purchase_order.number', '-') }}<br>
        Tanggal Surat Pesanan: {{ data_get($snapshot, 'stage.purchase_order.date', '-') }}<br>
        Paket: {{ data_get($snapshot, 'transaction.package_category', '-') }} |
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }}<br>
        Sekolah: {{ data_get($snapshot, 'school.name', '-') }} —
        {{ data_get($snapshot, 'school.address', '-') }}
    </p>
    <p>Pemeriksa: {{ data_get($snapshot, 'school.officials.inspector.name', '-') }}
        (NIP {{ data_get($snapshot, 'school.officials.inspector.nip', '-') }})</p>
    <table>
        <thead>
            <tr><th>No.</th><th>Barang/Jasa</th><th>Dipesan</th><th>Diterima</th><th>Kondisi</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'stage.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['item'] ?? '-' }}</td>
                    <td>{{ $item['ordered_quantity'] ?? '-' }}</td>
                    <td>{{ $item['quantity'] ?? '-' }}</td>
                    <td>{{ $item['condition'] ?? '-' }}</td>
                    <td>{{ $item['issue_notes'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
