<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Surat Pesanan - {{ data_get($snapshot, 'document_number', '-') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
        }

        h1,
        .center {
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 16px;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
        }

        .number {
            text-align: right;
        }

        .signatures {
            margin-top: 48px;
        }

        .signatures td {
            border: 0;
            text-align: center;
            width: 50%;
        }
    </style>
</head>

<body>
    @include('documents.partials.letterhead')
    <h1>SURAT PESANAN (SP)</h1>
    <p class="center">
        Nomor: {{ data_get($snapshot, 'document_number', '-') }}<br>
        Tanggal: {{ data_get($snapshot, 'document_date_numeric', '-') }}
    </p>
    <p>
        Sub pekerjaan: <strong>{{ data_get($snapshot, 'transaction.package_category', '-') }}</strong><br>
        Tahun anggaran: {{ data_get($snapshot, 'transaction.budget_year', '-') }}<br>
        Sumber dana: {{ data_get($snapshot, 'transaction.funding_source', '-') }}<br>
        Sekolah: {{ data_get($snapshot, 'school.name', '-') }}
    </p>

    <p>Yang bertanda tangan di bawah ini:</p>
    <table>
        <tbody>
            <tr>
                <th colspan="2">PIHAK PERTAMA — Sekolah</th>
                <th colspan="2">PIHAK KEDUA — Penyedia</th>
            </tr>
            <tr>
                <td>Nama</td>
                <td>{{ data_get($snapshot, 'school.officials.headmaster.name', '-') }}</td>
                <td>Nama</td>
                <td>{{ data_get($snapshot, 'subsidiary.director_name', '-') }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>{{ data_get($snapshot, 'school.officials.headmaster.title', 'Kepala Sekolah') }}</td>
                <td>Jabatan</td>
                <td>Direktur {{ data_get($snapshot, 'subsidiary.name', '-') }}</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>{{ data_get($snapshot, 'school.officials.headmaster.nip', '-') }}</td>
                <td>NPWP</td>
                <td>{{ data_get($snapshot, 'subsidiary.npwp', '-') }}</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>{{ data_get($snapshot, 'school.address', '-') }}</td>
                <td>Alamat</td>
                <td>{{ data_get($snapshot, 'subsidiary.address', '-') }}</td>
            </tr>
            <tr>
                <td>Kontak</td>
                <td>{{ data_get($snapshot, 'school.phone', '-') }} · {{ data_get($snapshot, 'school.email', '-') }}
                </td>
                <td>Kontak</td>
                <td>{{ data_get($snapshot, 'subsidiary.phone', '-') }} ·
                    {{ data_get($snapshot, 'subsidiary.email', '-') }}</td>
            </tr>
        </tbody>
    </table>

    <p>
        Berdasarkan kesepakatan harga untuk pekerjaan
        <strong>{{ data_get($snapshot, 'transaction.package_category', '-') }}</strong>,
        dengan ini dipesan barang sebagai berikut:
    </p>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Produk</th>
                <th>Spesifikasi</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Jumlah Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'transaction.items', []) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ data_get($item, 'item_name', '-') }}</td>
                    <td>{{ data_get($item, 'specification', '-') }}</td>
                    <td class="number">Rp
                        {{ number_format((float) data_get($item, 'negotiated_price', data_get($item, 'official_price', 0)), 0, ',', '.') }}
                    </td>
                    <td>{{ data_get($item, 'quantity', 0) }} {{ data_get($item, 'unit', '') }}</td>
                    <td class="number">Rp
                        {{ number_format((float) data_get($item, 'negotiated_amount', data_get($item, 'amount', 0)), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tbody>
            <tr>
                <td class="number" colspan="5">Subtotal</td>
                <td class="number">Rp
                    {{ number_format((float) data_get($snapshot, 'transaction.subtotal', 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="number" colspan="5">PPN</td>
                <td class="number">Rp
                    {{ number_format((float) data_get($snapshot, 'transaction.tax_breakdown.ppn.amount', 0), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="number" colspan="5">PPh 22</td>
                <td class="number">Rp
                    {{ number_format((float) data_get($snapshot, 'transaction.tax_breakdown.pph_22.amount', 0), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="number" colspan="5">PPh 23</td>
                <td class="number">Rp
                    {{ number_format((float) data_get($snapshot, 'transaction.tax_breakdown.pph_23.amount', 0), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <th class="number" colspan="5">Jumlah setelah pajak</th>
                <th class="number">Rp
                    {{ number_format((float) data_get($snapshot, 'transaction.grand_total', 0), 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>
    <p>
        Pesanan harus diterima paling lambat {{ data_get($snapshot, 'transaction.end_date', '-') }}.
        Durasi pekerjaan: {{ data_get($snapshot, 'transaction.duration', '-') }}.
    </p>
    <table class="signatures">
        <tr>
            <td>
                PIHAK PERTAMA<br>Kepala Sekolah<br><br><br><br>
                <strong>{{ data_get($snapshot, 'school.officials.headmaster.name', '________________________') }}</strong><br>
                NIP {{ data_get($snapshot, 'school.officials.headmaster.nip', '-') }}
            </td>
            <td>
                PIHAK KEDUA<br>{{ data_get($snapshot, 'subsidiary.name', '-') }}<br><br><br><br>
                <strong>{{ data_get($snapshot, 'subsidiary.director_name', '________________________') }}</strong><br>
                Direktur
            </td>
        </tr>
    </table>
</body>

</html>
