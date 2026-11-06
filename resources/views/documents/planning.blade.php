<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dokumen Perencanaan - {{ data_get($snapshot, 'transaction.package_category', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .number { text-align: right; }
    </style>
</head>
<body>
    @include('documents.partials.school-letterhead')
    <h1>DOKUMEN PERENCANAAN</h1>
    <p class="center">Nomor: {{ data_get($snapshot, 'document_number', '-') }}</p>
    <p>
        Sekolah: {{ data_get($snapshot, 'school.name', '-') }}<br>
        Alamat: {{ data_get($snapshot, 'school.address', '-') }}<br>
        Paket: {{ data_get($snapshot, 'transaction.package_category', '-') }}<br>
        Sumber anggaran: {{ data_get($snapshot, 'transaction.funding_source', '-') }}<br>
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }}<br>
        Mulai: {{ data_get($snapshot, 'transaction.start_date', '-') }} |
        Selesai: {{ data_get($snapshot, 'transaction.end_date', '-') }} |
        Lama pengerjaan: {{ data_get($snapshot, 'transaction.duration', '-') }}
    </p>
    <table>
        <thead>
            <tr><th>No.</th><th>Jenis</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            <tr><td>1</td><td>Jumlah Barang/Jasa</td><td>{{ data_get($snapshot, 'transaction.quantity_total', 0) }}</td></tr>
            <tr><td>2</td><td>Total Anggaran</td><td class="number">Rp {{ number_format((float) data_get($snapshot, 'transaction.subtotal', 0), 0, ',', '.') }}</td></tr>
            <tr><td>3</td><td>Persyaratan Penyedia</td><td>Identitas penyedia, NPWP, NIB, dan dokumen legalitas terkait.</td></tr>
        </tbody>
    </table>
</body>
</html>
