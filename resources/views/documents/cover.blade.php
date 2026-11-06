<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cover - {{ data_get($snapshot, 'transaction.package_category', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.6; }
        .cover { margin-top: 40px; text-align: center; }
        .details { border-collapse: collapse; margin: 40px auto; width: 80%; }
        .details td { padding: 8px; }
        .label { font-weight: bold; width: 30%; }
    </style>
</head>
<body>
    @include('documents.partials.school-letterhead')
    <div class="cover">
        <p><strong>KUITANSI:</strong></p>
        <p>{{ data_get($snapshot, 'documents.receipt.number') ?: '-' }}</p>
        <p>{{ data_get($snapshot, 'documents.receipt.date') ?: '-' }}</p>
        <p style="margin-top: 60px"><strong>PAKET PEKERJAAN</strong></p>
        <h2>{{ data_get($snapshot, 'transaction.package_category', '-') }}</h2>
        <p>DI LINGKUNGAN {{ data_get($snapshot, 'school.name', '-') }}</p>
        <table class="details">
            <tr>
                <td class="label">HARGA</td>
                <td>Rp {{ number_format((float) data_get($snapshot, 'transaction.amount', 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">TERBILANG</td>
                <td>{{ data_get($snapshot, 'transaction.amount_in_words', '-') }}</td>
            </tr>
            <tr>
                <td class="label">REKANAN PELAKSANA</td>
                <td>{{ data_get($snapshot, 'subsidiary.name', '-') }}</td>
            </tr>
            <tr>
                <td class="label">ALAMAT</td>
                <td>{{ data_get($snapshot, 'subsidiary.address', '-') }}</td>
            </tr>
        </table>
        <p>{{ data_get($snapshot, 'transaction.budget_year', '-') }}</p>
    </div>
</body>
</html>
