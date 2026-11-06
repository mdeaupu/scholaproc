<div class="space-y-5">
    <x-mary-card title="Pemrosesan Pesanan" separator shadow>
        <p class="mb-3 text-sm">Status: <strong>{{ $procurementRequest->status_label }}</strong></p>
        @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_PRICE_AGREED)
            <x-mary-button label="Mulai Pemrosesan & Terbitkan Surat Pesanan" icon="o-document-check"
                wire:click="startProcessing" spinner="startProcessing" class="bg-[#0046FF] text-white" />
        @endif
        @if ($procurementRequest->isTotalsLocked())
            <p class="mt-2 text-xs text-gray-600">
                Snapshot harga: subtotal Rp {{ number_format((float) $procurementRequest->subtotal, 0, ',', '.') }}.
                @if ($procurementRequest->isTaxConfigurationValidated())
                    Konfigurasi pajak tervalidasi.
                @else
                    Pajak belum tervalidasi; nilai pajak dan total final tidak ditampilkan.
                @endif
            </p>
        @endif
    </x-mary-card>

    @if (in_array($procurementRequest->status, [
        \App\Models\ProcurementRequest::STATUS_PROCESSING,
        \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
        \App\Models\ProcurementRequest::STATUS_DISPUTED,
    ], true))
        <x-mary-card title="Pengiriman Bertahap" separator shadow>
            <x-mary-form wire:submit="recordDelivery" class="space-y-3">
                <x-mary-input label="Tanggal pengiriman" type="date" wire:model="deliveryDate" />
                @foreach ($procurementRequest->items as $item)
                    <div class="grid grid-cols-3 items-center gap-3 text-sm">
                        <span class="col-span-2">{{ $item->item_name }} · dipesan {{ $item->quantity }}</span>
                        <x-mary-input label="Jumlah dikirim" type="number" min="0"
                            wire:model="deliveryQuantities.{{ $item->id }}" />
                    </div>
                @endforeach
                <x-mary-textarea label="Catatan pengiriman" wire:model="deliveryNotes" rows="2" />
                <x-mary-button label="Catat Pengiriman" type="submit" spinner="recordDelivery" />
            </x-mary-form>
            <ul class="mt-3 list-disc pl-5 text-sm">
                @foreach ($procurementRequest->deliveries as $delivery)
                    <li>{{ $delivery->delivered_at?->format('d-m-Y') }} ·
                        {{ $delivery->items->sum('quantity') }} unit
                    </li>
                @endforeach
            </ul>
        </x-mary-card>

        <x-mary-card title="Penerimaan Barang" separator shadow>
            <x-mary-form wire:submit="recordReceipt" class="space-y-3">
                <x-mary-input label="Tanggal penerimaan" type="date" wire:model="receiptDate" />
                @foreach ($procurementRequest->items as $item)
                    <div class="grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-4">
                        <span class="text-sm md:col-span-2">{{ $item->item_name }} ·
                            diterima {{ $receivedQuantities[$item->id] ?? 0 }}/{{ $item->quantity }}</span>
                        <x-mary-input label="Jumlah diterima" type="number" min="0"
                            wire:model="receiptRows.{{ $item->id }}.quantity" />
                        <x-mary-select label="Kondisi" wire:model="receiptRows.{{ $item->id }}.condition"
                            :options="[
                                ['id' => 'good', 'name' => 'Baik'],
                                ['id' => 'incomplete', 'name' => 'Kurang'],
                                ['id' => 'damaged', 'name' => 'Rusak'],
                                ['id' => 'non_conforming', 'name' => 'Tidak sesuai'],
                            ]" option-value="id" option-label="name" />
                        <x-mary-input label="Catatan kekurangan/kerusakan"
                            wire:model="receiptRows.{{ $item->id }}.issue_notes" class="md:col-span-4" />
                    </div>
                @endforeach
                <x-mary-textarea label="Catatan penerimaan" wire:model="receiptNotes" rows="2" />
                <x-mary-button label="Simpan Penerimaan (Draf)" type="submit" spinner="recordReceipt" />
            </x-mary-form>
            <div class="mt-4 space-y-2">
                @foreach ($procurementRequest->receipts as $receipt)
                    <div class="flex items-center justify-between rounded border p-3 text-sm">
                        <span>{{ $receipt->received_at?->format('d-m-Y') }} · {{ $receipt->items->sum('quantity') }} unit ·
                            {{ $receipt->verified_at ? 'Terverifikasi' : 'Draf' }}</span>
                        @if (! $receipt->verified_at)
                            <x-mary-button label="Verifikasi Penerimaan" wire:click="verifyReceipt({{ $receipt->id }})"
                                spinner="verifyReceipt" />
                        @endif
                    </div>
                @endforeach
            </div>
            @foreach ($openIssues as $issue)
                <div class="mt-3 rounded border border-amber-200 bg-amber-50 p-3">
                    <p class="text-sm">{{ $issue->requestItem?->item_name }}: {{ $issue->issue_notes }}</p>
                    <x-mary-input label="Tindak lanjut" wire:model="resolutionNotes" />
                    <x-mary-button label="Tandai Selesai" wire:click="resolveIssue({{ $issue->id }})"
                        spinner="resolveIssue" class="mt-2" />
                </div>
            @endforeach
        </x-mary-card>
    @endif

    @if (in_array($procurementRequest->status, [
        \App\Models\ProcurementRequest::STATUS_RECEIVED,
        \App\Models\ProcurementRequest::STATUS_INVOICED,
        \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
        \App\Models\ProcurementRequest::STATUS_COMPLETED,
    ], true))
        <x-mary-card title="Invoice dan Pembayaran" separator shadow>
            @if (! $procurementRequest->invoice)
                <x-mary-button label="Proses Invoice" icon="o-document-text" wire:click="processInvoice"
                    spinner="processInvoice" class="bg-[#0046FF] text-white" />
            @else
                <p class="text-sm">Invoice {{ $procurementRequest->invoice->invoice_number }} ·
                    total Rp {{ number_format((float) $procurementRequest->invoice->total_amount, 0, ',', '.') }}</p>
                @if (! $procurementRequest->invoice->payment)
                    <x-mary-form wire:submit="recordPayment" class="mt-3 grid gap-3 md:grid-cols-2">
                        <x-mary-input label="Jumlah pembayaran" type="number" min="0.01" step="0.01" wire:model="paymentAmount" />
                        <x-mary-input label="Metode pembayaran" wire:model="paymentMethod" />
                        <x-mary-input label="Referensi pembayaran" wire:model="paymentReference" />
                        <x-mary-input label="Tanggal pembayaran" type="date" wire:model="paymentDate" />
                        <x-mary-button label="Catat Pembayaran" type="submit" spinner="recordPayment" />
                    </x-mary-form>
                @elseif (! $procurementRequest->invoice->payment->confirmed_at)
                    <p class="mt-2 text-sm">Pembayaran dicatat, belum dikonfirmasi.</p>
                    <x-mary-button label="Konfirmasi Pembayaran & Terbitkan Kuitansi" wire:click="confirmPayment"
                        spinner="confirmPayment" class="mt-2 bg-[#0046FF] text-white" />
                @else
                    <p class="mt-2 text-sm text-green-700">Pembayaran terkonfirmasi.</p>
                @endif
            @endif
        </x-mary-card>
    @endif

    @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING)
        <x-mary-button label="Tandai Pengadaan Selesai" wire:click="complete" wire:confirm="Pastikan semua barang, dokumen, tindak lanjut, dan pembayaran telah lengkap."
            spinner="complete" class="bg-green-700 text-white" />
    @endif

    <x-mary-card title="Dokumen Terbit" separator shadow>
        <ul class="space-y-2 text-sm">
            @foreach ($procurementRequest->documents->whereNotNull('issued_at') as $document)
                <li>
                    {{ strtoupper(str_replace('_', ' ', $document->document_type)) }} · {{ $document->document_number }}
                    <a class="ml-2 text-blue-700 underline"
                        href="{{ route('procurement.documents.download', [$procurementRequest, $document]) }}">Unduh</a>
                </li>
            @endforeach
        </ul>
    </x-mary-card>
</div>
