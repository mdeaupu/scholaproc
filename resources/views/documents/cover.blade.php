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
<x-document-layout :title="'Cover - ' . $procurement->package_category_name">

    <div style="margin-top: 40px; text-align: center;">
        <p style="font-size: 13px; font-weight: bold; text-transform: uppercase;">
            {{ $letterHead['pusat'] }}
        </p>

        <div style="margin: 22px 0;">
            <img src="{{ public_path($logo ?? 'images/logo-jabar.svg') }}" style="width: 110px;" alt="Logo">
        </div>

        <p style="font-size: 15px; font-weight: bold; text-transform: uppercase;">{{ $school->name }}</p>
        <p style="font-size: 10px; margin-top: 4px;">{{ $school->address }}</p>
    </div>

    <div style="margin-top: 50px; text-align: center;">
        <p class="bold">KUITANSI :</p>
        <p>{{ $receiptDocument->document_number ?? '-' }}</p>
        <p>Tanggal {{ optional($receiptDocument->document_date)->translatedFormat('d F Y') ?? '-' }}</p>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <p class="bold">TENTANG :</p>
        <p class="bold uppercase" style="font-size: 13px;">PAKET PEKERJAAN</p>
        <p class="bold uppercase" style="font-size: 13px;">{{ $procurement->package_category_name }}</p>
        <p>DI LINGKUNGAN {{ strtoupper($school->name) }}</p>
    </div>

    <table class="info-table" style="margin-top: 40px; width: 80%; margin-left: auto; margin-right: auto;">
        <tr>
            <td class="label">HARGA</td>
            <td class="colon">:</td>
            <td>Rp {{ number_format($totalNegosiasi, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">TERBILANG</td>
            <td class="colon">:</td>
            <td>{{ \App\Helpers\Terbilang::make($totalNegosiasi, true) }}</td>
        </tr>
        <tr>
            <td class="label">REKANAN PELAKSANA</td>
            <td class="colon">:</td>
            <td>{{ $supplier->company_name }}</td>
        </tr>
        <tr>
            <td class="label">ALAMAT</td>
            <td class="colon">:</td>
            <td>{{ $supplier->address }}</td>
        </tr>
    </table>

    <div style="margin-top: 60px; text-align: center;">
        <p class="bold uppercase">{{ $cityName ?? '-' }}</p>
        <p>TAHUN ANGGARAN {{ $procurement->budget_year_name }}</p>
    </div>

</x-document-layout>
