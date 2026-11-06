<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Identitas Penyedia - {{ data_get($snapshot, 'subsidiary.name', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>IDENTITAS PENYEDIA</h1>
    <p class="center">Nomor: {{ data_get($snapshot, 'document_number', '-') }}</p>
    <p>Yang bertanda tangan di bawah ini menyatakan identitas penyedia sebagai berikut:</p>
    <table>
        <tbody>
            <tr><th>Nama perusahaan</th><td>{{ data_get($snapshot, 'subsidiary.name', '-') }}</td></tr>
            <tr><th>Alamat</th><td>{{ data_get($snapshot, 'subsidiary.address', '-') }}</td></tr>
            <tr><th>Telepon / Email</th><td>{{ data_get($snapshot, 'subsidiary.phone', '-') }} / {{ data_get($snapshot, 'subsidiary.email', '-') }}</td></tr>
            <tr><th>NPWP</th><td>{{ data_get($snapshot, 'subsidiary.npwp', '-') }}</td></tr>
            <tr><th>NIB</th><td>{{ data_get($snapshot, 'subsidiary.nib', '-') }}</td></tr>
            <tr><th>Direktur</th><td>{{ data_get($snapshot, 'subsidiary.director_name', '-') }}</td></tr>
            <tr><th>NIK Direktur</th><td>{{ data_get($snapshot, 'subsidiary.director_nik', '-') }}</td></tr>
            <tr><th>NPWP Direktur</th><td>{{ data_get($snapshot, 'subsidiary.director_npwp', '-') }}</td></tr>
        </tbody>
    </table>
    <h3>Akta dan izin usaha</h3>
    <table>
        <thead><tr><th>Jenis</th><th>Nomor</th><th>Tanggal</th><th>Notaris / Penerbit</th></tr></thead>
        <tbody>
            @foreach (data_get($snapshot, 'subsidiary.legal_documents', []) as $legalDocument)
                <tr>
                    <td>{{ $legalDocument['type'] ?? '-' }}</td>
                    <td>{{ $legalDocument['number'] ?? '-' }}</td>
                    <td>{{ $legalDocument['date'] ?? '-' }}</td>
                    <td>{{ $legalDocument['notary_name'] ?? $legalDocument['issuer'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>
        Saya menyatakan bahwa data dan informasi yang tercantum di atas benar dan dapat dipertanggungjawabkan.
    </p>
    <p style="margin-top: 48px; text-align: right;">
        {{ data_get($snapshot, 'subsidiary.name', '-') }}<br><br><br>
        <strong>{{ data_get($snapshot, 'subsidiary.director_name', '-') }}</strong><br>
        Direktur
    </p>
</body>
</html>
