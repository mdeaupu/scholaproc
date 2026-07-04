<x-document-layout :title="'Berita Acara Serah Terima - ' . $procurement->package_category">

    @include('documents.partials.kop-surat', ['school' => $school, 'schoolSetting' => $schoolSetting])

    <p class="doc-title">Berita Acara Serah Terima</p>
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
            <td>{{ $procurement->package_category }}</td>
        </tr>
        <tr>
            <td class="label">Tahun</td>
            <td class="colon">:</td>
            <td>{{ $procurement->budget_year }}</td>
        </tr>
    </table>

    <p class="note">Yang bertandatangan di bawah ini</p>

    <table class="info-table">
        <tr>
            <td style="width:16px; vertical-align:top;">1.</td>
            <td colspan="2">Nama</td>
            <td class="colon">:</td>
            <td>{{ $supplier->director_name }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Jabatan</td>
            <td class="colon">:</td>
            <td>Direktur</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Nama Penyedia</td>
            <td class="colon">:</td>
            <td>{{ $supplier->company_name }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Alamat Penyedia</td>
            <td class="colon">:</td>
            <td>{{ $supplier->address }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Nomor Telepon</td>
            <td class="colon">:</td>
            <td>{{ $supplier->director_phone ?: $supplier->phone }}</td>
        </tr>
        <tr>
            <td colspan="5" class="italic">Sebagai pihak yang menyerahkan, selanjutnya disebut PIHAK PERTAMA</td>
        </tr>
    </table>

    <div class="section-spacer"></div>

    <table class="info-table">
        <tr>
            <td style="width:16px; vertical-align:top;">2.</td>
            <td colspan="2">Nama</td>
            <td class="colon">:</td>
            <td>{{ $headmaster->name }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Jabatan</td>
            <td class="colon">:</td>
            <td>Kepala Sekolah</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Nama Satuan Pendidikan</td>
            <td class="colon">:</td>
            <td>{{ $school->name }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Alamat Satuan Pendidikan</td>
            <td class="colon">:</td>
            <td>{{ $school->address }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Nomor Telepon</td>
            <td class="colon">:</td>
            <td>{{ $school->phone_number }}</td>
        </tr>
        <tr>
            <td colspan="5" class="italic">Sebagai pihak yang menerima, selanjutnya disebut PIHAK KEDUA</td>
        </tr>
    </table>

    <p class="note">
        PIHAK PERTAMA menyerahkan hasil pekerjaan {{ $procurement->package_category }} kepada PIHAK KEDUA,
        dan PIHAK KEDUA telah menerima hasil pekerjaan tersebut dalam jumlah yang lengkap dan kondisi
        yang baik sesuai dengan rincian berikut:
    </p>

    <table class="items-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 26px;">No</th>
                <th rowspan="2" style="text-align: left;">Nama Barang/Jasa</th>
                <th rowspan="2">Jumlah Diserahkan</th>
                <th rowspan="2">Jumlah Diterima</th>
                <th colspan="2">Kondisi</th>
            </tr>
            <tr>
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
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">-</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="note">
        Berita Acara Serah Terima ini berfungsi sebagai bukti serah terima hasil pekerjaan kepada
        PIHAK KEDUA, untuk selanjutnya dicatat pada buku penerimaan barang satuan pendidikan.
    </p>
    <p class="note">
        Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana seharusnya.
    </p>

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
