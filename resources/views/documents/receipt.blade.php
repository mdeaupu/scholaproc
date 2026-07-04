<x-document-layout :title="'Kuitansi - ' . $document->document_number">

    <p class="doc-title" style="font-size: 16px;">Kuitansi Pembayaran</p>

    <table class="info-table" style="margin-top: 16px;">
        <tr>
            <td style="width: 55%;"></td>
            <td>
                <table class="info-table">
                    <tr>
                        <td class="label">Tahun Anggaran</td>
                        <td class="colon">:</td>
                        <td>{{ $procurement->budget_year }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nomor</td>
                        <td class="colon">:</td>
                        <td>{{ $document->document_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Sumber Dana</td>
                        <td class="colon">:</td>
                        <td>{{ $procurement->funding_source }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-spacer"></div>

    <table class="info-table">
        <tr>
            <td class="label">Sudah diterima dari</td>
            <td class="colon">:</td>
            <td>{{ $school->name }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah uang</td>
            <td class="colon">:</td>
            <td>Rp {{ number_format($totalAfterTax, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Terbilang</td>
            <td class="colon">:</td>
            <td>{{ \App\Helpers\Terbilang::make($totalAfterTax, true) }}</td>
        </tr>
        <tr>
            <td class="label">Untuk Pembayaran</td>
            <td class="colon">:</td>
            <td>{{ $procurement->package_category }}</td>
        </tr>
    </table>

    @include('documents.partials.signature-block', [
        'placeDate' =>
            ($cityName ?? 'Cianjur') .
            ', ' .
            \Carbon\Carbon::parse($document->document_date)->translatedFormat('d F Y'),
        'left' => [
            'title' => 'Diserahkan Oleh, Kepala Sekolah',
            'name' => $headmaster->name,
            'subtitle' => 'NIP. ' . ($headmaster->nip ?: '-'),
        ],
        'right' => [
            'title' => 'Diterima Oleh, ' . $supplier->company_name,
            'name' => $supplier->director_name,
            'subtitle' => 'Direktur',
        ],
    ])

    <p class="note">Barang/Pekerjaan tersebut telah diterima/diselesaikan dengan lengkap dan baik</p>

    <table style="margin-top: 10px;">
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
