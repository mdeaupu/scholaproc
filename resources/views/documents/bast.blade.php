<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>BAST - {{ $document->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        .letterhead { border-bottom: 2px solid #111; margin-bottom: 20px; padding-bottom: 12px; }
        .letterhead-image { display: block; max-height: 100px; max-width: 100%; margin: 0 auto 8px; }
        .letterhead-identity { text-align: center; line-height: 1.5; }
        h1, .center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .signatures { margin-top: 48px; width: 100%; }
        .signatures td { border: 0; text-align: center; width: 50%; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>BERITA ACARA SERAH TERIMA</h1>
    <p class="center">Nomor: {{ $document->document_number }}</p>
    <p>
        Berdasarkan Surat Pesanan Nomor {{ data_get($snapshot, 'stage.purchase_order.number', '-') }}
        tanggal {{ data_get($snapshot, 'stage.purchase_order.date', '-') }} untuk paket
        {{ data_get($snapshot, 'transaction.package_category', '-') }} Tahun Anggaran
        {{ data_get($snapshot, 'transaction.budget_year', '-') }}.
    </p>
    <p>
        Pada tanggal {{ data_get($snapshot, 'stage.received_at', '-') }},
        Anak Perusahaan <strong>{{ $subsidiary['name'] }}</strong> menyerahkan barang kepada
        <strong>{{ data_get($snapshot, 'school.name', '-') }}</strong>.
    </p>
    <table>
        <thead>
            <tr><th>No.</th><th>Nama Barang</th><th>Jumlah Diterima</th><th>Kondisi</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'stage.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['item'] ?? '-' }}</td>
                    <td>{{ $item['quantity'] ?? '-' }}</td>
                    <td>{{ $item['condition'] ?? '-' }}</td>
                    <td>{{ $item['issue_notes'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Dokumen ini mencatat penerimaan pada tahap yang disebutkan di atas.</p>
    <table class="signatures">
        <tr>
            <td>
                Pihak yang menyerahkan,<br>{{ $subsidiary['name'] }}<br><br><br><br>
                <strong>{{ $subsidiary['director_name'] }}</strong><br>Direktur
            </td>
            <td>
                Pihak yang menerima,<br>{{ data_get($snapshot, 'school.name', '-') }}<br><br><br><br>
                @php($receiver = collect(data_get($snapshot, 'stage.signatories', []))->firstWhere('role', 'headmaster'))
                <strong>{{ $receiver['name'] ?? '________________________' }}</strong><br>
                {{ $receiver['title'] ?? 'Penerima' }}
                @if (! empty($receiver['nip']))
                    <br>NIP {{ $receiver['nip'] }}
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
