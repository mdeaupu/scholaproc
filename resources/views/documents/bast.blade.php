<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>BAST - {{ data_get($snapshot, 'document_number', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .signatures { margin-top: 48px; }
        .signatures td { border: 0; text-align: center; width: 50%; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    @php
        $schoolHeadmaster = data_get($snapshot, 'school.officials.headmaster')
            ?? collect(data_get($snapshot, 'stage.signatories', []))->firstWhere('role', 'headmaster')
            ?? [];
    @endphp
    <h1>BERITA ACARA SERAH TERIMA (BAST)</h1>
    <p class="center">
        Nomor BAST: {{ data_get($snapshot, 'document_number', '-') }}<br>
        Tanggal: {{ data_get($snapshot, 'document_date_numeric', '-') }}
    </p>
    <p>
        Berdasarkan Surat Pesanan Nomor
        <strong>{{ data_get($snapshot, 'stage.purchase_order.number', '-') }}</strong>
        tanggal {{ data_get($snapshot, 'stage.purchase_order.date', '-') }} untuk pekerjaan
        <strong>{{ data_get($snapshot, 'transaction.package_category', '-') }}</strong>,
        Tahun Anggaran {{ data_get($snapshot, 'transaction.budget_year', '-') }}.
    </p>
    <p>
        Pada tanggal {{ data_get($snapshot, 'stage.received_at', '-') }},
        PIHAK KEDUA menyerahkan dan PIHAK PERTAMA menerima hasil pekerjaan
        {{ data_get($snapshot, 'transaction.package_category', '-') }}.
    </p>
    <table>
        <tbody>
            <tr>
                <th colspan="2">PIHAK PERTAMA — Penerima</th>
                <th colspan="2">PIHAK KEDUA — Penyerah</th>
            </tr>
            <tr>
                <td>Nama</td><td>{{ data_get($schoolHeadmaster, 'name', '-') }}</td>
                <td>Nama</td><td>{{ data_get($snapshot, 'subsidiary.director_name', '-') }}</td>
            </tr>
            <tr>
                <td>Jabatan</td><td>{{ data_get($schoolHeadmaster, 'title', 'Kepala Sekolah') }}</td>
                <td>Jabatan</td><td>Direktur {{ data_get($snapshot, 'subsidiary.name', '-') }}</td>
            </tr>
            <tr>
                <td>NIP</td><td>{{ data_get($schoolHeadmaster, 'nip', '-') }}</td>
                <td>NPWP</td><td>{{ data_get($snapshot, 'subsidiary.npwp', '-') }}</td>
            </tr>
            <tr>
                <td>Alamat</td><td>{{ data_get($snapshot, 'school.address', '-') }}</td>
                <td>Alamat</td><td>{{ data_get($snapshot, 'subsidiary.address', '-') }}</td>
            </tr>
            <tr>
                <td>Kontak</td>
                <td>{{ data_get($snapshot, 'school.phone', '-') }} · {{ data_get($snapshot, 'school.email', '-') }}</td>
                <td>Kontak</td>
                <td>{{ data_get($snapshot, 'subsidiary.phone', '-') }} · {{ data_get($snapshot, 'subsidiary.email', '-') }}</td>
            </tr>
        </tbody>
    </table>
    <table>
        <thead>
            <tr><th>No.</th><th>Nama Barang/Jasa</th><th>Jumlah Diserahkan</th><th>Jumlah Diterima</th><th>Kondisi</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'stage.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ data_get($item, 'item', '-') }}</td>
                    <td>{{ data_get($item, 'ordered_quantity', '-') }} {{ data_get($item, 'unit', '') }}</td>
                    <td>{{ data_get($item, 'quantity', '-') }} {{ data_get($item, 'unit', '') }}</td>
                    <td>{{ data_get($item, 'condition', '-') }}</td>
                    <td>{{ data_get($item, 'issue_notes', '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>
        Dokumen ini mencatat serah terima pada tahap {{ data_get($snapshot, 'stage.verified_at', '-') }}.
        Catatan penerimaan: {{ data_get($snapshot, 'stage.receipt_notes', '-') }}
    </p>
    <table class="signatures">
        <tr>
            <td>
                PIHAK PERTAMA,<br>Kepala Sekolah<br><br><br><br>
                <strong>{{ data_get($schoolHeadmaster, 'name', '________________________') }}</strong><br>
                NIP {{ data_get($schoolHeadmaster, 'nip', '-') }}
            </td>
            <td>
                PIHAK KEDUA,<br>{{ data_get($snapshot, 'subsidiary.name', '-') }}<br><br><br><br>
                <strong>{{ data_get($snapshot, 'subsidiary.director_name', '________________________') }}</strong><br>
                Direktur
            </td>
        </tr>
    </table>
</body>
</html>
