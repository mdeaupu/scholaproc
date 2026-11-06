<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi - {{ data_get($snapshot, 'document_number', '-') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .signatures { margin-top: 48px; }
        .signatures td { border: 0; text-align: center; width: 33%; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>KUITANSI PEMBAYARAN</h1>
    <p class="center">
        Nomor: {{ data_get($snapshot, 'document_number', '-') }}<br>
        Tanggal: {{ data_get($snapshot, 'document_date_numeric', '-') }}
    </p>
    <p>
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }}<br>
        Sumber dana: {{ data_get($snapshot, 'transaction.funding_source', '-') }}<br>
        Nama paket pekerjaan: {{ data_get($snapshot, 'transaction.package_category', '-') }}
    </p>
    <p>
        Telah diterima dari: <strong>{{ data_get($snapshot, 'school.name', '-') }}</strong><br>
        Untuk pembayaran pekerjaan: {{ data_get($snapshot, 'transaction.package_category', '-') }}<br>
        Metode pembayaran: {{ data_get($snapshot, 'transaction.payment.method', '-') }}<br>
        Tanggal pembayaran: {{ data_get($snapshot, 'transaction.payment.paid_at', '-') }}<br>
        Tanggal konfirmasi: {{ data_get($snapshot, 'transaction.payment.confirmed_at', '-') }}<br>
        Referensi pembayaran: {{ data_get($snapshot, 'transaction.payment.reference', '-') }}
    </p>
    <p>
        Jumlah pembayaran:
        <strong>Rp {{ number_format((float) data_get($snapshot, 'transaction.payment.amount', data_get($snapshot, 'transaction.amount', 0)), 0, ',', '.') }}</strong><br>
        Terbilang: <strong>{{ data_get($snapshot, 'transaction.amount_in_words', '-') }}</strong>
    </p>
    <table class="signatures">
        <tr>
            <td>
                Diserahkan oleh,<br>Kepala Sekolah<br><br><br><br>
                <strong>{{ data_get($snapshot, 'transaction.receipt_signatories.school', '________________________') }}</strong>
                @if (data_get($snapshot, 'school.officials.headmaster.nip'))
                    <br>NIP {{ data_get($snapshot, 'school.officials.headmaster.nip') }}
                @endif
            </td>
            <td>
                Pejabat pembayaran,<br>Bendahara<br><br><br><br>
                <strong>{{ data_get($snapshot, 'transaction.receipt_signatories.treasurer', '________________________') }}</strong>
                @if (data_get($snapshot, 'school.officials.treasurer.nip'))
                    <br>NIP {{ data_get($snapshot, 'school.officials.treasurer.nip') }}
                @endif
            </td>
            <td>
                Diterima oleh,<br>{{ data_get($snapshot, 'subsidiary.name', '-') }}<br><br><br><br>
                <strong>{{ data_get($snapshot, 'transaction.receipt_signatories.provider', '________________________') }}</strong><br>
                Direktur
            </td>
        </tr>
    </table>
</body>
</html>
