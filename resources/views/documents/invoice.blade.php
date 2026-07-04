<x-document-layout :title="'Faktur - ' . $document->document_number">

    <p class="doc-title" style="font-size: 16px;">Faktur</p>

    <div class="section-spacer"></div>

    <table class="info-table">
        <tr>
            <td style="width: 55%;">
                <p>Kepada Yth.</p>
                <p class="bold">{{ $school->name }}</p>
                <p>{{ $school->address }}</p>
            </td>
            <td>
                <table class="info-table">
                    <tr>
                        <td class="label">No. Faktur</td>
                        <td class="colon">:</td>
                        <td>{{ $document->document_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal</td>
                        <td class="colon">:</td>
                        <td>{{ \Carbon\Carbon::parse($document->document_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th style="text-align: left;">Nama Produk</th>
                <th>Satuan</th>
                <th>Jumlah (Qty)</th>
                <th>Harga Satuan</th>
                <th>Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        {{ number_format($item->official_price ?? $item->estimated_price, 0, ',', '.') }}</td>
                    <td class="text-right">
                        {{ number_format($item->officialAmount() > 0 ? $item->officialAmount() : $item->estimatedAmount(), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="5" class="text-right">Jumlah Harga Jual</td>
                <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($procurement->is_taxable)
                <tr>
                    <td colspan="5" class="text-right">PPN
                        {{ rtrim(rtrim(number_format($procurement->ppn_rate, 2), '0'), '.') }}%</td>
                    <td class="text-right">{{ number_format($ppn, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">PPh 22</td>
                    <td class="text-right">{{ number_format($pph22, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">PPh 23</td>
                    <td class="text-right">{{ number_format($pph23, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="totals-row">
                <td colspan="5" class="text-right">Jumlah Harga Setelah Pajak</td>
                <td class="text-right">{{ number_format($totalAfterTax, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p class="note italic">Terbilang: {{ \App\Helpers\Terbilang::make($totalAfterTax, true) }}</p>

    <table class="signature-table">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $headmaster->name }}</p>
                <p>NIP. {{ $headmaster->nip ?: '-' }}</p>
            </td>
            <td>
                <p>Hormat Kami,</p>
                <p>{{ $supplier->company_name }}</p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $supplier->director_name }}</p>
                <p>Direktur</p>
            </td>
        </tr>
    </table>

</x-document-layout>
