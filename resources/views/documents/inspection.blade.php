<x-document-layout :title="'Surat Hasil Pemeriksaan - ' . $procurement->package_category_name">

    @include('documents.partials.kop-surat', ['school' => $school, 'schoolSetting' => $schoolSetting])

    <p class="doc-title">Surat Hasil Pemeriksaan</p>
    <p class="doc-number">{{ $document->document_number }}</p>

    <div class="section-spacer"></div>

    <table class="info-table">
        <tr>
            <td class="label">Nomor Surat Pemesanan</td>
            <td class="colon">:</td>
            <td>{{ $purchaseOrderDocument->document_number }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($purchaseOrderDocument->document_date)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Pekerjaan</td>
            <td class="colon">:</td>
            <td>{{ $procurement->package_category_name }}</td>
        </tr>
        <tr>
            <td class="label">Tahun</td>
            <td class="colon">:</td>
            <td>{{ $procurement->budget_year_name }}</td>
        </tr>
    </table>

    <p class="note">Yang bertandatangan di bawah ini</p>

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
            <td class="label">Nama Satuan Pendidikan</td>
            <td class="colon">:</td>
            <td>{{ $school->name }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Satuan Pendidikan</td>
            <td class="colon">:</td>
            <td>{{ $school->address }}</td>
        </tr>
    </table>

    <p class="note">Telah melakukan pemeriksaan terhadap hasil pekerjaan sesuai surat pemesanan dimaksud dengan
        rincian berikut :</p>

    <table class="items-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 26px;">No</th>
                <th rowspan="2" style="text-align: left;">Nama Barang/Jasa</th>
                <th rowspan="2">Jumlah</th>
                <th colspan="3">Jumlah Yang Diterima</th>
            </tr>
            <tr>
                <th>Kurang</th>
                <th>Baik</th>
                <th>Rusak</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">-</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="note">Demikian Surat Pemeriksaan Hasil Pekerjaan ini dibuat dengan sebenarnya untuk dipergunakan
        sebagaimana seharusnya.</p>

    <table class="signature-table">
        <tr>
            <td>
                <p>PIHAK KEDUA</p>
                <p>Kepala Sekolah</p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $headmaster->name }}</p>
                <p>NIP. {{ $headmaster->nip ?: '-' }}</p>
            </td>
            <td>
                <p>PIHAK PERTAMA</p>
                <p>{{ $supplier->company_name }}</p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $supplier->director_name }}</p>
                <p>Direktur</p>
            </td>
        </tr>
    </table>

    <table style="margin-top: 20px;">
        <tr>
            <td style="width: 30%;"></td>
            <td style="width: 40%; text-align: center;">
                <p>Pemeriksa Barang</p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $inspector->name }}</p>
                <p>NIP. {{ $inspector->nip ?: '-' }}</p>
            </td>
            <td style="width: 30%;"></td>
        </tr>
    </table>

</x-document-layout>
