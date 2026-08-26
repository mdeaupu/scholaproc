@php
    $letterHead = $schoolSetting
        ? $schoolSetting->getLetterHead()
        : [
            'pusat' => '',
            'provinsi' => '',
            'sub_wilayah' => null,
            'sekolah' => strtoupper($school->name),
            'alamat_lengkap' => $school->address,
            'kontak' => 'Telp: ' . $school->phone_number,
        ];
@endphp

<x-document-layout :title="'Identitas Penyedia - ' . $supplier->company_name">

    @include('documents.partials.kop-surat', ['school' => $school, 'schoolSetting' => $schoolSetting])

    <p class="doc-title">Identitas Penyedia</p>
    <p class="doc-number">{{ $document->document_number ?? '-' }}</p>
    <p class="text-center" style="font-size: 11px;">Tanggal {{ \Carbon\Carbon::parse($document->document_date)->translatedFormat('d F Y') ?? '-' }}</p>

    <div class="section-spacer"></div>

    <p>Yang bertanda tangan di bawah ini :</p>

    <table class="info-table" style="margin-top: 6px;">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td>{{ $supplier->director_name }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td>Direktur CV {{ $supplier->company_name }}</td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="colon">:</td>
            <td>{{ $supplier->director_nik }}</td>
        </tr>
        <tr>
            <td class="label">NPWP</td>
            <td class="colon">:</td>
            <td>{{ $supplier->director_npwp ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td>{{ $supplier->director_address ?: '-' }}</td>
        </tr>
    </table>

    <p style="margin-top: 12px;">Dengan ini menyatakan dengan sesungguhnya bahwa :</p>

    <div style="margin-top: 8px;">
        <p><strong>1. Identitas Perusahaan</strong></p>
        <table class="info-table" style="margin-left: 20px;">
            <tr>
                <td class="label">Nama Perusahaan</td>
                <td class="colon">:</td>
                <td>{{ $supplier->company_name }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td>{{ $supplier->address }}</td>
            </tr>
            <tr>
                <td class="label">NPWP</td>
                <td class="colon">:</td>
                <td>{{ $supplier->npwp }}</td>
            </tr>
            <tr>
                <td class="label">NIB</td>
                <td class="colon">:</td>
                <td>{{ $supplier->nib }}</td>
            </tr>
        </table>
    </div>

    @if ($supplier->commissioner_name)
        <div style="margin-top: 12px;">
            <p><strong>2. Data Komisaris</strong></p>
            <table class="info-table" style="margin-left: 20px;">
                <tr>
                    <td class="label">Nama</td>
                    <td class="colon">:</td>
                    <td>{{ $supplier->commissioner_name }}</td>
                </tr>
                <tr>
                    <td class="label">NIK</td>
                    <td class="colon">:</td>
                    <td>{{ $supplier->commissioner_nik ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Alamat</td>
                    <td class="colon">:</td>
                    <td>{{ $supplier->commissioner_address ?: '-' }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div style="margin-top: 14px;">
        <p>{{ $supplier->commissioner_name ? '3' : '2' }}. Bahwa kami tidak sedang dalam proses hukum, pailit, atau dikenakan sanksi oleh instansi pemerintah.</p>
        <p style="margin-top: 6px;">{{ $supplier->commissioner_name ? '4' : '3' }}. Bahwa data dan informasi yang kami berikan adalah benar dan dapat dipertanggungjawabkan.</p>
    </div>

    <p style="margin-top: 14px;">Demikian surat pernyataan ini dibuat dengan sebenarnya.</p>

    @include('documents.partials.signature-single', [
        'placeDate' => $placeDate,
        'signer' => [
            'title' => 'Direktur CV ' . $supplier->company_name,
            'name' => $supplier->director_name,
            'subtitle' => 'NIK. ' . ($supplier->director_nik ?: '-'),
        ],
    ])

</x-document-layout>
