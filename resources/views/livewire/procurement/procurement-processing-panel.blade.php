@php
    $deliveryStatuses = [
        \App\Models\ProcurementRequest::STATUS_PROCESSING,
        \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
        \App\Models\ProcurementRequest::STATUS_DISPUTED,
    ];
    $receivedStatuses = [
        \App\Models\ProcurementRequest::STATUS_RECEIVED,
        \App\Models\ProcurementRequest::STATUS_INVOICED,
        \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
        \App\Models\ProcurementRequest::STATUS_COMPLETED,
    ];
    $canRecordDelivery = in_array($procurementRequest->status, $deliveryStatuses, true);
    $isCompleted = $procurementRequest->status === \App\Models\ProcurementRequest::STATUS_COMPLETED;
    $hasSignatories = $procurementRequest->hasSignatories();

    $latestDelivery = $procurementRequest->deliveries->sortByDesc('id')->first();
    $deliveryDocument =
        $latestDelivery !== null
            ? $procurementRequest->documents->first(
                fn($document): bool => $document->document_type === 'delivery' &&
                    $document->issued_at !== null &&
                    data_get($document->document_snapshot, 'stage.delivery_id') == $latestDelivery->id,
            )
            : null;
    $deliveryNumber =
        $deliveryDocument?->document_number ??
        ($latestDelivery !== null
            ? 'KIR-' .
                $latestDelivery->delivered_at->format('Y') .
                '-' .
                str_pad((string) $latestDelivery->id, 4, '0', STR_PAD_LEFT)
            : null);

    $shipmentRows = $procurementRequest->items
        ->map(function ($item, $index): array {
            $shipped = $item->deliveredQuantity();
            $received = $item->receivedQuantity();
            $status =
                $received >= (int) $item->quantity && $received > 0
                    ? 'Lengkap'
                    : ($received > 0
                        ? 'Sebagian'
                        : ($shipped > 0
                            ? 'Dikirim'
                            : 'Menunggu'));

            return [
                'no' => $index + 1,
                'item_name' => $item->item_name,
                'specification' => $item->specification ?: '-',
                'ordered' => $item->quantity,
                'shipped' => $shipped,
                'received' => $received,
                'unit' => $item->unit?->name ?? '-',
                'status' => $status,
            ];
        })
        ->all();
    $totalShipped = array_sum(array_column($shipmentRows, 'shipped'));
    $totalReceived = array_sum(array_column($shipmentRows, 'received'));

    $preparationDone = in_array(
        $procurementRequest->status,
        array_merge([\App\Models\ProcurementRequest::STATUS_PRICE_AGREED], $deliveryStatuses, $receivedStatuses),
        true,
    );
    $deliveryFinished = in_array($procurementRequest->status, $receivedStatuses, true);
    $latestReceipt = $procurementRequest->receipts->sortByDesc('id')->first();

    $conditionLabels = [
        'good' => 'Baik',
        'incomplete' => 'Kurang',
        'damaged' => 'Rusak',
        'non_conforming' => 'Tidak Sesuai',
    ];
    $pendingReceipts = $procurementRequest->receipts
        ->filter(fn($receipt): bool => $receipt->verified_at === null && $receipt->voided_at === null)
        ->sortByDesc('id')
        ->values();

    $shipmentSteps = [
        [
            'label' => 'Pengajuan Disetujui',
            'state' => 'done',
            'description' => null,
            'date' => $procurementRequest->reviewed_at,
        ],
        [
            'label' => 'Persiapan Pengiriman',
            'state' => $preparationDone ? 'done' : 'pending',
            'description' => $preparationDone
                ? 'Barang disiapkan oleh penyedia'
                : 'Menunggu penetapan harga dan penerbitan surat pesanan',
            'date' => $preparationDone ? $procurementRequest->totals_locked_at : null,
        ],
        [
            'label' => 'Dalam Pengiriman',
            'state' => $deliveryFinished ? 'done' : ($canRecordDelivery ? 'active' : 'pending'),
            'description' =>
                $deliveryFinished || $canRecordDelivery
                    ? 'Barang sedang dikirim ke sekolah'
                    : 'Menunggu barang dikirim ke sekolah',
            'date' => $deliveryFinished || $canRecordDelivery ? $latestDelivery?->delivered_at : null,
        ],
        [
            'label' => 'Selesai Pengiriman',
            'state' => $deliveryFinished ? 'done' : 'pending',
            'description' => $deliveryFinished
                ? 'Barang diterima oleh sekolah'
                : 'Menunggu konfirmasi penerimaan dari sekolah',
            'date' => $deliveryFinished ? $latestReceipt?->verified_at ?? $procurementRequest->verified_at : null,
        ],
    ];

    $cardClass = 'rounded-lg border border-gray-200 bg-white shadow-sm';
    $labelClass = 'w-36 shrink-0 text-sm text-gray-500';
    $valueClass = 'min-w-0 break-words text-sm font-semibold text-gray-900';
@endphp

<div class="space-y-5" x-data="{ showDeliveryForm: false }">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-gray-900">Pengiriman Barang</h2>
            <p class="mt-1 text-sm text-gray-500">
                Status: <strong class="text-gray-900">{{ $procurementRequest->status_label }}</strong>
            </p>
            @if ($procurementRequest->isTotalsLocked())
                <p class="mt-1 text-xs text-gray-500">
                    Snapshot harga: subtotal Rp {{ number_format((float) $procurementRequest->subtotal, 0, ',', '.') }}.
                    @if ($procurementRequest->isTaxConfigurationValidated())
                        Konfigurasi pajak tervalidasi.
                    @else
                        Pajak belum tervalidasi; nilai pajak dan total final tidak ditampilkan.
                    @endif
                </p>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @if ($canRecordDelivery)
                <x-mary-button label="Buat Pengiriman" icon="o-plus" @click="showDeliveryForm = true"
                    class="bg-[#0046FF] text-white" />
            @elseif ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_PRICE_AGREED)
                <x-mary-button label="Mulai Pemrosesan & Terbitkan Surat Pesanan" icon="o-document-check"
                    wire:click="startProcessing" spinner="startProcessing" :disabled="!$hasSignatories"
                    class="{{ $hasSignatories ? 'bg-[#0046FF] text-white' : 'bg-gray-300 text-gray-600' }}" />
            @endif
        </div>
    </div>

    @if (!$isCompleted && !$hasSignatories)
        <x-mary-alert icon="o-exclamation-triangle" class="border border-amber-200 bg-amber-50 text-amber-800">
            <strong>Penandatangan Dokumen Sekolah belum lengkap.</strong> Lengkapi nama Kepala Sekolah, Pemeriksa
            Barang, dan Bendahara Sekolah pada panel kanan sebelum memulai pemrosesan atau menerbitkan dokumen resmi.
        </x-mary-alert>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <x-mary-card title="Informasi Pengiriman" separator :class="$cardClass">
                <x-slot:menu>
                    @if ($canRecordDelivery && $latestDelivery)
                        <x-mary-button label="Edit" icon="o-pencil-square" @click="showDeliveryForm = true"
                            class="btn-xs border border-gray-200 bg-white text-gray-700" />
                    @endif
                </x-slot:menu>

                <div class="grid gap-x-6 gap-y-3 md:grid-cols-2">
                    <div class="flex gap-2">
                        <span class="{{ $labelClass }}">No. Pengiriman</span>
                        <span class="shrink-0 text-sm text-gray-400">:</span>
                        <span class="{{ $valueClass }}">{{ $deliveryNumber ?? '-' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="{{ $labelClass }}">Tanggal Pengiriman</span>
                        <span class="shrink-0 text-sm text-gray-400">:</span>
                        <span
                            class="{{ $valueClass }}">{{ $latestDelivery?->delivered_at?->format('d M Y') ?? '-' }}</span>
                    </div>
                    <div class="flex gap-2 md:col-span-2">
                        <span class="{{ $labelClass }}">Catatan</span>
                        <span class="shrink-0 text-sm text-gray-400">:</span>
                        <span class="{{ $valueClass }}">{{ $latestDelivery?->notes ?: '-' }}</span>
                    </div>
                </div>

                @unless ($latestDelivery)
                    <p class="mt-4 text-xs text-gray-500">Belum ada pengiriman tercatat.</p>
                @endunless

                @if ($procurementRequest->deliveries->count() > 1)
                    <div class="mt-4 border-t border-gray-100 pt-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Riwayat Pengiriman</p>
                        <ul class="mt-1 space-y-1 text-xs text-gray-500">
                            @foreach ($procurementRequest->deliveries->sortByDesc('id') as $history)
                                <li>
                                    {{ $history->delivered_at?->format('d M Y') ?? '-' }} ·
                                    {{ $history->items->sum('quantity') }} unit
                                    @if ($history->notes)
                                        · {{ $history->notes }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-mary-card>

            {{-- Tabel Terpadu: Pesanan, Pengiriman, Penerimaan & Status --}}
            <x-mary-card title="Daftar Barang Pengadaan" separator :class="$cardClass">
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <x-mary-table :headers="[
                        ['key' => 'no', 'label' => 'No', 'class' => 'w-10 text-right'],
                        ['key' => 'item_name', 'label' => 'Nama Barang'],
                        ['key' => 'specification', 'label' => 'Spesifikasi'],
                        ['key' => 'ordered', 'label' => 'Dipesan', 'class' => 'text-right'],
                        ['key' => 'shipped', 'label' => 'Dikirim', 'class' => 'text-right'],
                        ['key' => 'received', 'label' => 'Diterima', 'class' => 'text-right'],
                        ['key' => 'unit', 'label' => 'Satuan'],
                        ['key' => 'status', 'label' => 'Status', 'class' => 'text-center'],
                    ]" :rows="$shipmentRows" class="text-sm">
                        @scope('cell_status', $row)
                            @php
                                $badgeClass = match ($row['status']) {
                                    'Lengkap' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'Sebagian' => 'border-amber-200 bg-amber-50 text-amber-700',
                                    'Dikirim' => 'border-blue-200 bg-blue-50 text-blue-700',
                                    default => 'border-gray-200 bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <span
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                {{ $row['status'] }}
                            </span>
                        @endscope
                        <x-slot:footer>
                            <tr>
                                <td class="font-bold text-gray-900" colspan="4">Total</td>
                                <td class="text-right font-bold text-gray-900">{{ $totalShipped }}</td>
                                <td class="text-right font-bold text-emerald-700">{{ $totalReceived }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </x-slot:footer>
                    </x-mary-table>
                </div>
            </x-mary-card>

            @if ($canRecordDelivery)
                <div x-show="showDeliveryForm" x-cloak x-transition.opacity.duration.200ms>
                    <x-mary-card title="Pengiriman Bertahap" separator :class="$cardClass">
                        <x-slot:menu>
                            <x-mary-button label="Tutup" icon="o-x-mark" @click="showDeliveryForm = false"
                                class="btn-xs text-gray-500" />
                        </x-slot:menu>
                        <x-mary-form wire:submit="recordDelivery" class="space-y-3">
                            <x-mary-input label="Tanggal pengiriman" type="date" wire:model="deliveryDate" />
                            @foreach ($procurementRequest->items as $item)
                                <div class="grid grid-cols-3 items-center gap-3 text-sm">
                                    <span class="col-span-2">{{ $item->item_name }} · dipesan {{ $item->quantity }} ·
                                        terkirim {{ $item->deliveredQuantity() }} · sisa kirim
                                        {{ $item->remainingToShip() }}</span>
                                    <x-mary-input label="Jumlah dikirim" type="number" min="0"
                                        wire:model="deliveryQuantities.{{ $item->id }}" />
                                </div>
                            @endforeach
                            <x-mary-textarea label="Catatan pengiriman" wire:model="deliveryNotes" rows="2" />
                            <x-mary-button label="Catat Pengiriman" type="submit" spinner="recordDelivery" />
                        </x-mary-form>
                    </x-mary-card>
                </div>
            @endif

            {{-- Verifikasi Draf Sekolah --}}
            @if ($canRecordDelivery || $pendingReceipts->isNotEmpty())
                <x-mary-card title="Daftar Penerimaan Menunggu Verifikasi" separator :class="$cardClass">
                    @if ($pendingReceipts->isNotEmpty() && !$hasSignatories)
                        <p class="mb-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Lengkapi <strong>Penandatangan Dokumen Sekolah</strong> di panel kanan terlebih dahulu
                            sebelum memverifikasi penerimaan dan menerbitkan BAST.
                        </p>
                    @endif

                    @if ($pendingReceipts->isEmpty())
                        <p class="text-sm text-gray-500">Belum ada draf penerimaan dari sekolah yang menunggu
                            verifikasi.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($pendingReceipts as $receipt)
                                <div class="rounded border border-gray-200 bg-gray-50 p-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ $receipt->received_at?->format('d M Y') ?? '-' }}
                                                · {{ $receipt->items->sum('quantity') }} unit
                                                · dicatat {{ $receipt->recordedBy?->name ?? '-' }}
                                            </p>
                                            <ul class="mt-1 space-y-0.5">
                                                @foreach ($receipt->items as $line)
                                                    <li class="text-xs text-gray-600">
                                                        {{ $line->requestItem?->item_name ?? '-' }}:
                                                        {{ $line->quantity }} ·
                                                        {{ $conditionLabels[$line->condition] ?? $line->condition }}
                                                        @if ($line->issue_notes)
                                                            · {{ $line->issue_notes }}
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                            @if ($receipt->notes)
                                                <p class="mt-1 text-xs text-gray-500">Catatan: {{ $receipt->notes }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <x-mary-badge value="Draf Sekolah" class="bg-blue-100 text-blue-700" />
                                            <x-mary-button label="Verifikasi Penerimaan" icon="o-check-circle"
                                                wire:click="verifyReceipt({{ $receipt->id }})" spinner="verifyReceipt"
                                                :disabled="!$hasSignatories"
                                                class="border-none {{ $hasSignatories ? 'bg-emerald-600 text-white' : 'bg-gray-300 text-gray-600' }}" />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-mary-card>
            @endif

            {{-- Tindak Lanjut Isu --}}
            @if ($openIssues->isNotEmpty())
                <x-mary-card title="Tindak Lanjut Isu Penerimaan" separator :class="$cardClass">
                    <div class="space-y-4">
                        @foreach ($openIssues as $issue)
                            <div class="rounded border border-amber-200 bg-amber-50 p-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ $issue->requestItem?->item_name ?? 'Barang' }}
                                            · {{ $conditionLabels[$issue->condition] ?? $issue->condition }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-600">{{ $issue->issue_notes }}</p>
                                    </div>
                                    <x-mary-badge value="Terbuka" class="bg-amber-100 text-amber-700" />
                                </div>
                                <div class="mt-3">
                                    <x-mary-input label="Catatan penyelesaian" wire:model="resolutionNotes"
                                        placeholder="Minimal 5 karakter" />
                                    <div class="mt-2">
                                        <x-mary-button label="Tandai Selesai" icon="o-check"
                                            wire:click="resolveIssue({{ $issue->id }})" spinner="resolveIssue"
                                            :disabled="!$hasSignatories"
                                            class="{{ $hasSignatories ? 'bg-[#0046FF] text-white' : 'bg-gray-300 text-gray-600' }}" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-mary-card>
            @endif

            @if (in_array($procurementRequest->status, $receivedStatuses, true))
                <x-mary-card title="Invoice dan Pembayaran" separator :class="$cardClass">
                    @if (!$procurementRequest->invoice)
                        @if ($procurementRequest->canProcessInvoice())
                            <x-mary-button label="Proses Invoice" icon="o-document-text" wire:click="processInvoice"
                                spinner="processInvoice" class="bg-[#0046FF] text-white" />
                        @else
                            @php
                                $invoiceGuardLabels = [
                                    'status_received' => 'Status penerimaan belum Received',
                                    'all_quantities_received' => 'Penerimaan belum penuh terverifikasi',
                                    'no_open_follow_ups' => 'Masih ada tindak lanjut isu terbuka',
                                    'has_signatories' => 'Penandatangan sekolah belum lengkap',
                                    'tax_configuration_valid' => 'Konfigurasi pajak belum tervalidasi',
                                    'totals_locked' => 'Snapshot harga belum dikunci',
                                    'bast_issued' => 'BAST belum diterbitkan',
                                    'not_yet_invoiced' => 'Invoice sudah pernah diproses',
                                ];
                                $eligibility = $procurementRequest->invoiceEligibility();
                                $failedInvoiceGuards = collect($eligibility)
                                    ->filter(fn(bool $passed): bool => !$passed)
                                    ->keys()
                                    ->map(fn(string $key): string => $invoiceGuardLabels[$key] ?? $key)
                                    ->implode(', ');
                                $needsTaxValidation = !($eligibility['tax_configuration_valid'] ?? true);
                            @endphp
                            <x-mary-button label="Proses Invoice" icon="o-document-text" disabled
                                class="bg-gray-300 text-gray-600" />
                            <x-mary-alert icon="o-exclamation-triangle"
                                class="mt-3 border border-amber-200 bg-amber-50 text-amber-800">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span>Syarat invoice belum lengkap: {{ $failedInvoiceGuards }}</span>
                                    @if ($needsTaxValidation)
                                        <button type="button"
                                            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-[#0046FF] hover:underline">
                                            <span>Ke Evaluasi Aturan Pajak</span>
                                            <x-mary-icon name="o-arrow-up" class="h-3.5 w-3.5" />
                                        </button>
                                    @endif
                                </div>
                            </x-mary-alert>
                        @endif
                    @else
                        <p class="text-sm">Invoice {{ $procurementRequest->invoice->invoice_number }} ·
                            total Rp
                            {{ number_format((float) $procurementRequest->invoice->total_amount, 0, ',', '.') }}</p>
                        @if (!$procurementRequest->invoice->payment)
                            <x-mary-form wire:submit="recordPayment" class="mt-3 grid gap-3 md:grid-cols-2">
                                <x-mary-input label="Jumlah pembayaran" type="number" min="0.01" step="0.01"
                                    wire:model="paymentAmount" />
                                <x-mary-input label="Metode pembayaran" wire:model="paymentMethod" />
                                <x-mary-input label="Referensi pembayaran" wire:model="paymentReference" />
                                <x-mary-input label="Tanggal pembayaran" type="date" wire:model="paymentDate" />
                                <x-mary-button label="Catat Pembayaran" type="submit" spinner="recordPayment" />
                            </x-mary-form>
                        @elseif (!$procurementRequest->invoice->payment->confirmed_at)
                            <p class="mt-2 text-sm">Pembayaran dicatat, belum dikonfirmasi.</p>
                            <x-mary-button label="Konfirmasi Pembayaran & Terbitkan Kuitansi"
                                wire:click="confirmPayment" spinner="confirmPayment"
                                class="mt-2 bg-[#0046FF] text-white" />
                        @else
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-green-700">Pembayaran terkonfirmasi.</p>
                                @if (!$procurementRequest->hasIssuedDocument('receipt'))
                                    <x-mary-button label="Terbitkan Kuitansi" icon="o-document-check"
                                        wire:click="issueReceipt" spinner="issueReceipt"
                                        class="bg-[#0046FF] text-white" />
                                @endif
                            </div>
                        @endif
                    @endif
                </x-mary-card>
            @endif

            @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING)
                @php
                    $completionGuardLabels = [
                        'status_payment_pending' => 'Status transaksi belum menunggu pembayaran',
                        'has_items' => 'Daftar barang kosong',
                        'has_subsidiary' => 'Anak Perusahaan belum ditetapkan',
                        'tax_configuration_valid' => 'Konfigurasi pajak belum tervalidasi',
                        'all_quantities_received' => 'Penerimaan barang belum penuh',
                        'no_open_follow_ups' => 'Tindak lanjut penerimaan masih terbuka',
                        'has_signatories' =>
                            'Penandatangan sekolah (Kepala Sekolah, Pemeriksa, Bendahara) belum lengkap',
                        'has_official_prices' => 'Harga resmi barang belum lengkap',
                        'all_items_negotiation_settled' => 'Negosiasi barang belum selesai',
                        'administrative_processing_completed' => 'Verifikasi penerimaan atau faktur belum tercatat',
                        'payment_confirmed' => 'Pembayaran terkonfirmasi belum sesuai total faktur',
                        'purchase_order_issued' => 'Surat Pesanan belum diterbitkan',
                        'bast_issued' => 'BAST belum diterbitkan',
                        'invoice_issued' => 'Faktur belum diterbitkan',
                    ];
                    $completionEligibility = $procurementRequest->completionEligibility();
                    $failedCompletionGuards = collect($completionEligibility)
                        ->filter(fn(bool $passed): bool => !$passed)
                        ->keys()
                        ->map(fn(string $key): string => $completionGuardLabels[$key] ?? $key)
                        ->implode(', ');
                @endphp

                @if ($procurementRequest->canComplete())
                    <x-mary-button label="Tandai Pengadaan Selesai" wire:click="complete"
                        wire:confirm="Pastikan semua barang, dokumen, tindak lanjut, dan pembayaran telah lengkap."
                        spinner="complete" class="bg-green-700 text-white" />
                @else
                    <x-mary-button label="Tandai Pengadaan Selesai" disabled class="bg-gray-300 text-gray-600" />
                    <x-mary-alert icon="o-exclamation-triangle"
                        class="border border-amber-200 bg-amber-50 text-amber-800">
                        Syarat penyelesaian belum lengkap: {{ $failedCompletionGuards }}
                    </x-mary-alert>
                @endif
            @endif
        </div>

        <div class="space-y-5 xl:col-span-1">
            <x-mary-card title="Status Pengiriman" separator :class="$cardClass">
                <ol class="ml-3 border-l border-gray-200">
                    @foreach ($shipmentSteps as $step)
                        <li class="relative mb-6 ml-6 last:mb-0">
                            <span @class([
                                'absolute -left-9 top-0 flex h-6 w-6 items-center justify-center rounded-full',
                                'bg-emerald-500 text-white' => $step['state'] === 'done',
                                'bg-[#0046FF] text-white' => $step['state'] === 'active',
                                'border border-gray-200 bg-white text-gray-300' =>
                                    $step['state'] === 'pending',
                            ])>
                                <x-mary-icon :name="$step['state'] === 'active' ? 'o-truck' : 'o-check'" class="h-3.5 w-3.5" />
                            </span>
                            <p @class([
                                'text-sm font-bold',
                                'text-[#0046FF]' => $step['state'] === 'active',
                                'text-gray-900' => $step['state'] !== 'active',
                            ])>{{ $step['label'] }}</p>
                            @if ($step['state'] !== 'done' && $step['description'])
                                <p class="mt-0.5 text-xs text-gray-500">{{ $step['description'] }}</p>
                            @endif
                            @if ($step['date'])
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($step['date'])->format('d M Y') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-mary-card>

            <x-mary-card title="Penandatangan Dokumen Sekolah" separator :class="$cardClass">
                <x-slot:menu>
                    @if ($hasSignatories)
                        <x-mary-badge value="Lengkap" class="bg-emerald-100 text-emerald-700" />
                    @else
                        <x-mary-badge value="Belum Lengkap" class="bg-amber-100 text-amber-700" />
                    @endif
                </x-slot:menu>

                @if ($isCompleted)
                    <div class="space-y-3">
                        @foreach ([
        'headmaster' => 'Kepala Sekolah',
        'inspector' => 'Pemeriksa Barang',
        'treasurer' => 'Bendahara Sekolah',
    ] as $roleKey => $roleTitle)
                            @php
                                $savedSignatory = $procurementRequest->signatories->firstWhere('role', $roleKey);
                            @endphp
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <p class="text-xs font-semibold text-gray-500">
                                    {{ $savedSignatory?->title ?? $roleTitle }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">
                                    {{ $savedSignatory?->name ?? '-' }}</p>
                                @if ($savedSignatory?->nip)
                                    <p class="text-xs text-gray-500">NIP. {{ $savedSignatory->nip }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mb-3 text-xs text-gray-500">
                        Wajib diisi di awal agar tercantum pada Surat Pesanan, Surat Hasil Pemeriksaan, BAST, Kuitansi,
                        dan Cover.
                    </p>

                    <x-mary-form wire:submit="saveSignatories" class="space-y-3">
                        @foreach ([
        'headmaster' => 'Kepala Sekolah',
        'inspector' => 'Pemeriksa Barang',
        'treasurer' => 'Bendahara Sekolah',
    ] as $roleKey => $roleTitle)
                            <div class="space-y-2 rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                                <p class="text-xs font-bold text-gray-700">{{ $roleTitle }}</p>
                                <x-mary-input label="Nama Lengkap"
                                    wire:model="signatoriesForm.{{ $roleKey }}.name" />
                                <div class="grid grid-cols-2 gap-2">
                                    <x-mary-input label="NIP (Opsional)"
                                        wire:model="signatoriesForm.{{ $roleKey }}.nip" />
                                    <x-mary-input label="Jabatan"
                                        wire:model="signatoriesForm.{{ $roleKey }}.title" />
                                </div>
                            </div>
                        @endforeach

                        <x-mary-button label="Simpan Penandatangan" icon="o-check" type="submit"
                            spinner="saveSignatories" class="w-full bg-[#0046FF] text-white" />
                    </x-mary-form>
                @endif
            </x-mary-card>
        </div>
    </div>
</div>
