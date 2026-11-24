<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Faktur - {{ data_get($snapshot, 'stage.invoice_number', data_get($snapshot, 'document_number', '-')) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.5; }
        h1 { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .number { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    @include('documents.partials.letterhead')
    <h1>FAKTUR / INVOICE</h1>
    <p>
        Nomor: {{ data_get($snapshot, 'stage.invoice_number', data_get($snapshot, 'document_number', '-')) }}<br>
        Tanggal: {{ data_get($snapshot, 'stage.invoice_date', data_get($snapshot, 'document_date_numeric', '-')) }}
    </p>
    <p>
        <strong>Ditagihkan kepada:</strong><br>
        {{ data_get($snapshot, 'school.name', '-') }}<br>
        {{ data_get($snapshot, 'school.address', '-') }}
        @if (data_get($snapshot, 'school.postal_code'))
            , {{ data_get($snapshot, 'school.postal_code') }}
        @endif
        <br>
        Telepon: {{ data_get($snapshot, 'school.phone', '-') }}<br>
        Email: {{ data_get($snapshot, 'school.email', '-') }}
    </p>
    <p>
        Uraian pekerjaan: {{ data_get($snapshot, 'transaction.package_category', '-') }}<br>
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }} ·
        Sumber dana: {{ data_get($snapshot, 'transaction.funding_source', '-') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>No.</th><th>Barang/Jasa</th><th>Spesifikasi</th><th>Satuan</th><th>Jumlah</th><th>Harga Satuan</th><th>Jumlah Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'transaction.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ data_get($item, 'item_name', '-') }}</td>
                    <td>{{ data_get($item, 'specification', '-') }}</td>
                    <td>{{ data_get($item, 'unit', '-') }}</td>
                    <td>{{ data_get($item, 'quantity', 0) }}</td>
                    <td class="number">Rp {{ number_format((float) data_get($item, 'negotiated_price', data_get($item, 'official_price', 0)), 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) data_get($item, 'negotiated_amount', data_get($item, 'amount', 0)), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table>
        <tbody>
            <tr>
                <td colspan="6" class="number">Subtotal</td>
                <td class="number">Rp {{ number_format((float) data_get($snapshot, 'stage.subtotal', data_get($snapshot, 'transaction.subtotal', 0)), 0, ',', '.') }}</td>
            </tr>
            @foreach (['ppn' => 'PPN', 'pph_22' => 'PPh 22', 'pph_23' => 'PPh 23'] as $taxKey => $taxLabel)
                <tr>
                    <td colspan="6" class="number">
                        {{ $taxLabel }}
                        @if (data_get($snapshot, "transaction.tax_breakdown.{$taxKey}.rate") !== null)
                            ({{ data_get($snapshot, "transaction.tax_breakdown.{$taxKey}.rate") }}%)
                        @endif
                    </td>
                    <td class="number">Rp {{ number_format((float) data_get($snapshot, "transaction.tax_breakdown.{$taxKey}.amount", 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="6" class="number">Total Tagihan</td>
                <td class="number">Rp {{ number_format((float) data_get($snapshot, 'stage.total_amount', data_get($snapshot, 'transaction.grand_total', 0)), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @if (! data_get($snapshot, 'transaction.tax_breakdown.validated', false))
        <p>Konfigurasi pajak belum tervalidasi; rincian pajak di atas perlu diperiksa sebelum faktur digunakan.</p>
    @endif
</body>
</html>
