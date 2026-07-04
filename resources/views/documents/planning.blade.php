<x-document-layout :title="'Dokumen Perencanaan - ' . $procurement->package_category">

    @include('documents.partials.kop-surat', ['school' => $school, 'schoolSetting' => $schoolSetting])

    <p class="doc-title">Dokumen Perencanaan</p>
    <p class="doc-number">{{ $document->document_number }}</p>

    <div class="section-spacer"></div>

    <table class="info-table">
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
        <tr>
            <td class="label">Kategori Barang/Jasa</td>
            <td class="colon">:</td>
            <td>{{ $procurement->package_category }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="text-align: left;">Jenis</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>Jumlah Barang/Jasa</td>
                <td class="text-center">{{ $totalItems }}</td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td>Total Anggaran</td>
                <td class="text-right">Rp {{ number_format($totalAnggaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-center">3</td>
                <td>Tanggal Mulai Pekerjaan</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($procurement->start_date)->translatedFormat('d F Y') }}
                </td>
            </tr>
            <tr>
                <td class="text-center">4</td>
                <td>Tanggal Selesai Pekerjaan</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($procurement->end_date)->translatedFormat('d F Y') }}
                </td>
            </tr>
            <tr>
                <td class="text-center">5</td>
                <td>Lama Pengerjaan</td>
                <td class="text-center">{{ $procurement->work_duration_text }}</td>
            </tr>
            <tr>
                <td class="text-center">6</td>
                <td>Sumber Anggaran</td>
                <td class="text-center">{{ $procurement->funding_source }}</td>
            </tr>
            <tr>
                <td class="text-center">7</td>
                <td>Tahun Anggaran</td>
                <td class="text-center">{{ $procurement->budget_year }}</td>
            </tr>
            <tr>
                <td class="text-center">8</td>
                <td>Persyaratan Penyedia</td>
                <td>
                    Perorangan/Badan Usaha memenuhi syarat sebagai berikut:<br>
                    A. Identitas Penyedia<br>
                    B. Nomor Pokok Wajib Pajak (NPWP)<br>
                    C. Nomor Induk Berusaha (NIB)<br>
                    D. Bukti Laporan Pajak Tahun Terakhir
                </td>
            </tr>
        </tbody>
    </table>

    @include('documents.partials.signature-single', [
        'placeDate' => $placeDate,
        'signer' => [
            'title' => 'Pelaksana',
            'name' => $headmaster->name,
            'subtitle' => 'NIP. ' . ($headmaster->nip ?: '-'),
        ],
    ])

</x-document-layout>
