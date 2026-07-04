<x-document-layout :title="'Dokumen Hasil Negosiasi - ' . $procurement->package_category">

    @include('documents.partials.kop-surat', ['school' => $school, 'schoolSetting' => $schoolSetting])

    <p class="doc-title">Dokumen Hasil Negosiasi</p>
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
            <td class="label">Nama Calon Penyedia</td>
            <td class="colon">:</td>
            <td>{{ $supplier->company_name }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Calon Penyedia</td>
            <td class="colon">:</td>
            <td>{{ $supplier->address }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Terima Barang/Jasa</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($procurement->end_date)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Hasil Negosiasi</td>
            <td class="colon">:</td>
            <td>Rp
                {{ number_format($procurement->officialSubtotal() > 0 ? $procurement->officialSubtotal() : $procurement->estimatedSubtotal(), 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th style="text-align: left;">Nama Produk</th>
                <th>Jumlah (Qty)</th>
                <th>Harga Penawaran</th>
                <th>Harga Negosiasi</th>
                <th>Jumlah Harga Penawaran</th>
                <th>Jumlah Harga Negosiasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->estimated_price, 0, ',', '.') }}</td>
                    <td class="text-right">
                        {{ number_format($item->official_price ?? $item->estimated_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->estimatedAmount(), 0, ',', '.') }}</td>
                    <td class="text-right">
                        {{ number_format($item->officialAmount() > 0 ? $item->officialAmount() : $item->estimatedAmount(), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2" class="text-center">Jumlah Total</td>
                <td class="text-center">{{ $items->sum('quantity') }}</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format($procurement->estimatedSubtotal(), 0, ',', '.') }}</td>
                <td class="text-right">
                    {{ number_format($procurement->officialSubtotal() > 0 ? $procurement->officialSubtotal() : $procurement->estimatedSubtotal(), 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    @include('documents.partials.signature-block', [
        'placeDate' => $placeDate,
        'left' => [
            'title' => 'Calon Penyedia',
            'name' => $supplier->director_name,
            'subtitle' => 'Direktur',
        ],
        'right' => [
            'title' => 'Pelaksana',
            'name' => $headmaster->name,
            'subtitle' => 'NIP. ' . ($headmaster->nip ?: '-'),
        ],
    ])

</x-document-layout>
