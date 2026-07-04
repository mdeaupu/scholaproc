<x-document-layout :title="'Surat Pesanan - ' . $procurement->package_category">

    @include('documents.partials.kop-surat', ['school' => $school, 'schoolSetting' => $schoolSetting])

    <p class="doc-title">Surat Pesanan (SP)</p>
    <p class="doc-number">{{ $document->document_number }}</p>
    <p class="text-center">Tanggal {{ \Carbon\Carbon::parse($document->document_date)->translatedFormat('d F Y') }}</p>

    <div class="section-spacer"></div>

    <p>Sub Pekerjaan :</p>
    <p class="bold">{{ $procurement->package_category }}</p>
    <p>Pada {{ $school->name }}</p>

    <div class="section-spacer"></div>

    <p>Yang bertanda tangan di bawah ini :</p>

    <table class="info-table" style="margin-top: 6px;">
        <tr>
            <td style="width: 16px; vertical-align: top;">1.</td>
            <td colspan="3">
                <table class="info-table">
                    <tr>
                        <td class="label">Nama</td>
                        <td class="colon">:</td>
                        <td>{{ $headmaster->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td>Kepala Sekolah</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat</td>
                        <td class="colon">:</td>
                        <td>{{ $school->address }}</td>
                    </tr>
                </table>
                <p class="italic">Yang selanjutnya disebut PIHAK KE 1</p>
            </td>
        </tr>
    </table>

    <div class="section-spacer"></div>

    <table class="info-table">
        <tr>
            <td style="width: 16px; vertical-align: top;">2.</td>
            <td colspan="3">
                <table class="info-table">
                    <tr>
                        <td class="label">Nama</td>
                        <td class="colon">:</td>
                        <td>{{ $supplier->director_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td>Direktur</td>
                    </tr>
                    <tr>
                        <td class="label">NPWP</td>
                        <td class="colon">:</td>
                        <td>{{ $supplier->director_npwp ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Perusahaan</td>
                        <td class="colon">:</td>
                        <td>{{ $supplier->address }}</td>
                    </tr>
                </table>
                <p class="italic">Yang selanjutnya disebut PIHAK KE 2</p>
            </td>
        </tr>
    </table>

    <div class="section-spacer"></div>

    <p class="note">
        Berdasarkan dokumen perencanaan nomor {{ $planningDocument->document_number }} pada kegiatan
        {{ $procurement->package_category }}, dengan ini kami sampaikan rincian pesanan sebagai berikut :
    </p>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th style="text-align: left;">Nama Produk</th>
                <th>Harga Satuan</th>
                <th>Qty</th>
                <th>Jml Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-right">
                        {{ number_format($item->official_price ?? $item->estimated_price, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        {{ number_format($item->officialAmount() > 0 ? $item->officialAmount() : $item->estimatedAmount(), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="4" class="text-right">Jumlah Total</td>
                <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($procurement->is_taxable)
                <tr>
                    <td colspan="4" class="text-right">PPN
                        {{ rtrim(rtrim(number_format($procurement->ppn_rate, 2), '0'), '.') }}%</td>
                    <td class="text-right">{{ number_format($ppn, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right">PPh 22</td>
                    <td class="text-right">{{ number_format($pph22, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right">PPh 23</td>
                    <td class="text-right">{{ number_format($pph23, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="totals-row">
                <td colspan="4" class="text-right">Jumlah Setelah Pajak</td>
                <td class="text-right">{{ number_format($totalAfterTax, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-spacer"></div>

    <p>Pesanan tersebut harus diterima paling lambat :</p>
    <table class="info-table">
        <tr>
            <td class="label">Hari</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($procurement->end_date)->translatedFormat('l') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($procurement->end_date)->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <p class="note">Demikian Surat Pesanan ini dibuat untuk dapat dilaksanakan.</p>

    @include('documents.partials.signature-single', [
        'placeDate' => null,
        'signer' => [
            'title' => 'Kepala Sekolah',
            'name' => $headmaster->name,
            'subtitle' => 'NIP. ' . ($headmaster->nip ?: '-'),
        ],
    ])

</x-document-layout>
