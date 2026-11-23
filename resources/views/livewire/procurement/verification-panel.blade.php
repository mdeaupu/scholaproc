@php
    $user = auth()->user();
    $isSchool = $user->isSchool();
    $isVerified = $procurementRequest->verified_at !== null;
    $totalFiles = $photos->count() + $documents->count();
    $hasSignedDoc = $procurementRequest->hasVerificationEvidence();
    $getFileUrl = fn($path) => asset('storage/' . $path);

    $cardClass = 'bg-white shadow-sm rounded-lg border border-gray-200';

    $deliveries = $procurementRequest->deliveries->sortByDesc('id');
    $latestDelivery = $deliveries->first();
    $receipts = $procurementRequest->receipts->sortByDesc('id');

    $recordingStatuses = [
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
    $canRecordReceipt = $isSchool && in_array($procurementRequest->status, $recordingStatuses, true);
    $remainingToReceive = $procurementRequest->items->sum(fn($item): int => $item->remainingToReceive());
    $isReceiptCompleted = $isVerified || in_array($procurementRequest->status, $receivedStatuses, true);
    $hasSchoolLeftSection = $remainingToReceive > 0 || !$isVerified;

    $step2State =
        $receipts->isNotEmpty() || $isReceiptCompleted ? 'done' : ($latestDelivery !== null ? 'active' : 'pending');
    $step3State = $isReceiptCompleted ? 'done' : ($receipts->isNotEmpty() ? 'active' : 'pending');
    $shipmentSteps = [
        [
            'label' => 'Pengiriman',
            'state' => $latestDelivery !== null ? 'done' : 'pending',
            'date' => $latestDelivery?->delivered_at?->locale('id')->isoFormat('D MMM YYYY'),
        ],
        [
            'label' => 'Dalam Proses',
            'state' => $step2State,
            'date' => null,
        ],
        [
            'label' => $isReceiptCompleted ? 'Diterima' : 'Menunggu Konfirmasi',
            'state' => $step3State,
            'date' => $isReceiptCompleted
                ? $procurementRequest->verified_at?->locale('id')->isoFormat('D MMM YYYY')
                : null,
        ],
    ];

    $conditionMap = [
        'good' => ['Baik', 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        'incomplete' => ['Kurang', 'border-red-200 bg-red-50 text-red-700'],
        'damaged' => ['Rusak', 'border-red-200 bg-red-50 text-red-700'],
        'non_conforming' => ['Tidak Sesuai', 'border-red-200 bg-red-50 text-red-700'],
    ];

    $tableRows = $procurementRequest->items
        ->map(function ($item, $index) use ($conditionMap) {
            $latestReceiptItem = $item->receiptItems
                ->filter(
                    fn($receiptItem): bool => $receiptItem->receipt !== null &&
                        $receiptItem->receipt->voided_at === null,
                )
                ->sortByDesc(fn($receiptItem): int => (int) $receiptItem->receipt->id)
                ->first();
            [$conditionLabel, $conditionClass] = $conditionMap[$latestReceiptItem?->condition] ?? [
                'Menunggu',
                'border-gray-200 bg-gray-100 text-gray-500',
            ];
            $unit = $item->unit?->name;

            return [
                'no' => $index + 1,
                'item_name' => $item->item_name,
                'ordered' => $item->quantity . ($unit ? ' ' . $unit : ''),
                'shipped' => $item->deliveredQuantity() . ($unit ? ' ' . $unit : ''),
                'received' => $item->receivedQuantity() . ($unit ? ' ' . $unit : ''),
                'kondisi' => $conditionLabel,
                'kondisi_class' => $conditionClass,
            ];
        })
        ->all();

    $timeline = collect();
    foreach ($deliveries as $delivery) {
        $timeline->push([
            'at' => $delivery->delivered_at,
            'time' => $delivery->delivered_at?->locale('id')->isoFormat('D MMM YYYY'),
            'title' => 'Barang dikirim oleh ' . ($delivery->recordedBy?->name ?? 'Admin Pusat'),
            'desc' => $delivery->notes ?: $delivery->items->sum('quantity') . ' unit dikirim',
        ]);
    }
    foreach ($receipts as $receipt) {
        $timeline->push([
            'at' => $receipt->received_at,
            'time' => $receipt->received_at?->locale('id')->isoFormat('D MMM YYYY'),
            'title' => 'Penerimaan dicatat oleh ' . ($receipt->recordedBy?->name ?? '-'),
            'desc' =>
                $receipt->items->sum('quantity') . ' unit diterima' . ($receipt->notes ? ' · ' . $receipt->notes : ''),
        ]);
        if ($receipt->voided_at) {
            $timeline->push([
                'at' => \Carbon\Carbon::parse($receipt->voided_at),
                'time' => \Carbon\Carbon::parse($receipt->voided_at)->locale('id')->isoFormat('D MMM YYYY HH:mm'),
                'title' => 'Penerimaan dibatalkan',
                'desc' => $receipt->void_reason ?? null,
            ]);
        }
        if ($receipt->verified_at) {
            $timeline->push([
                'at' => $receipt->verified_at,
                'time' => $receipt->verified_at->locale('id')->isoFormat('D MMM YYYY HH:mm'),
                'title' => 'Penerimaan diverifikasi oleh ' . ($receipt->verifiedBy?->name ?? 'Admin Pusat'),
                'desc' => null,
            ]);
        }
    }
    if ($isVerified) {
        $timeline->push([
            'at' => $procurementRequest->verified_at,
            'time' => $procurementRequest->verified_at->locale('id')->isoFormat('D MMM YYYY HH:mm'),
            'title' => 'Konfirmasi penerimaan oleh ' . ($procurementRequest->verifiedBy?->name ?? '-'),
            'desc' => 'Barang telah diverifikasi diterima oleh sekolah',
        ]);
    }
    $timeline = $timeline
        ->filter(fn($event): bool => $event['at'] !== null)
        ->sortByDesc(fn($event): int => $event['at']->getTimestamp())
        ->values();
@endphp

<div class="space-y-5">

    {{-- ─── SECTION 1: Informasi & Status Pengiriman (Khusus Sekolah) ─── --}}
    @if ($isSchool)
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="{{ $cardClass }} p-5 xl:col-span-2">
                <h4 class="mb-4 font-bold text-gray-900">Informasi Pengiriman</h4>
                @if ($latestDelivery !== null)
                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs text-gray-500">Tanggal Pengiriman</p>
                            <p class="mt-0.5 text-sm font-bold text-gray-900">
                                {{ $latestDelivery->delivered_at?->locale('id')->isoFormat('D MMM YYYY') ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Petugas Pengirim</p>
                            <p class="mt-0.5 text-sm font-bold text-gray-900">
                                {{ $latestDelivery->recordedBy?->name ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Keterangan</p>
                            <p class="mt-0.5 text-sm font-bold text-gray-900">{{ $latestDelivery->notes ?: '-' }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Belum ada pengiriman yang tercatat.</p>
                @endif
            </div>

            <div class="{{ $cardClass }} p-5">
                <h4 class="mb-4 font-bold text-gray-900">Status Pengiriman</h4>
                <ul class="steps w-full text-xs">
                    @foreach ($shipmentSteps as $step)
                        <li class="step {{ in_array($step['state'], ['done', 'active'], true) ? 'step-primary' : '' }}">
                            <span class="font-semibold text-gray-900">{{ $step['label'] }}</span>
                            @if ($step['date'])
                                <br><span class="text-[10px] font-normal text-gray-500">{{ $step['date'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- ─── SECTION 2: Daftar Barang Diterima (Khusus Sekolah) ─── --}}
        <div class="{{ $cardClass }} p-5">
            <h4 class="mb-4 font-bold text-gray-900">Daftar Barang yang Diterima</h4>
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <x-mary-table :headers="[
                    ['key' => 'no', 'label' => 'No', 'class' => 'w-12 text-center'],
                    ['key' => 'item_name', 'label' => 'Nama Barang'],
                    ['key' => 'ordered', 'label' => 'Jumlah Pesanan', 'class' => 'text-center'],
                    ['key' => 'shipped', 'label' => 'Jumlah Dikirim', 'class' => 'text-center'],
                    ['key' => 'received', 'label' => 'Jumlah Diterima', 'class' => 'text-center'],
                    ['key' => 'kondisi', 'label' => 'Kondisi'],
                ]" :rows="$tableRows" class="text-sm">
                    @scope('cell_kondisi', $row)
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $row['kondisi_class'] }}">
                            {{ $row['kondisi'] }}
                        </span>
                    @endscope
                </x-mary-table>
            </div>
        </div>
    @endif

    {{-- ─── SECTION 3: Form Penerimaan & Riwayat ─── --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        @if ($isSchool && $hasSchoolLeftSection)
            <div class="space-y-5 xl:col-span-2">
                @if ($remainingToReceive > 0)
                    <div class="{{ $cardClass }} p-5">
                        <h4 class="mb-1 font-bold text-gray-900">Form Penerimaan</h4>
                        <p class="mb-5 text-xs text-gray-500">Catat tanggal, jumlah, dan kondisi barang yang diterima
                            sekolah.</p>

                        @if ($canRecordReceipt)
                            <x-mary-form wire:submit="recordReceipt">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <x-mary-input label="Tanggal Penerimaan" type="date" wire:model="receiptDate"
                                        required />
                                    <x-mary-file label="Bukti Penerimaan" wire:model="receiptProof" />
                                </div>

                                <div class="mt-5 space-y-4">
                                    @foreach ($procurementRequest->items as $item)
                                        <div
                                            class="grid grid-cols-1 items-end gap-4 rounded-lg border border-gray-100 bg-gray-50 p-4 md:grid-cols-4">
                                            <div class="md:col-span-2">
                                                <p class="text-sm font-bold text-gray-900">{{ $item->item_name }}</p>
                                                <p class="mt-1 text-xs text-gray-500">Pesanan: {{ $item->quantity }}
                                                    &middot; Dikirim: {{ $item->deliveredQuantity() }}</p>
                                            </div>
                                            <x-mary-input label="Jumlah" type="number" min="0"
                                                wire:model="receiptRows.{{ $item->id }}.quantity" :suffix="$item->unit?->name"
                                                required />
                                            <x-mary-select label="Kondisi"
                                                wire:model="receiptRows.{{ $item->id }}.condition"
                                                :options="[
                                                    ['id' => 'good', 'name' => 'Baik'],
                                                    ['id' => 'incomplete', 'name' => 'Kurang'],
                                                    ['id' => 'damaged', 'name' => 'Rusak'],
                                                    ['id' => 'non_conforming', 'name' => 'Tidak Sesuai'],
                                                ]" option-value="id" option-label="name" required />
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-5 flex justify-start">
                                    <x-mary-button label="Simpan Penerimaan" icon="o-paper-airplane" type="submit"
                                        spinner="recordReceipt" class="btn-primary text-white" />
                                </div>
                            </x-mary-form>
                        @else
                            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500">
                                Pencatatan penerimaan hanya tersedia saat pesanan sedang diproses, sebagian diterima,
                                atau dalam sengketa.
                            </p>
                        @endif
                    </div>
                @endif

                @if (!$isVerified)
                    <div class="{{ $cardClass }} p-5">
                        <h4 class="mb-1 text-sm font-bold text-black">Foto Bukti Penerimaan</h4>
                        <p class="mb-4 text-xs text-gray-500">Unggah foto kondisi barang saat diterima (JPG/PNG, maks
                            5MB).</p>
                        <form wire:submit="uploadPhoto" class="space-y-3">
                            <div x-data="{ isDragging: false }" x-on:dragover.prevent="isDragging = true"
                                x-on:dragleave="isDragging = false" x-on:drop.prevent="isDragging = false"
                                :class="isDragging ? 'border-[#0046FF] bg-[#0046FF]/5' :
                                    'border-gray-300 bg-gray-50 hover:border-[#0046FF]/50'"
                                class="relative rounded-xl border-2 border-dashed p-6 text-center transition-colors">
                                <input type="file" multiple accept="image/jpeg,image/png" wire:model="photoFiles"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" />
                                <x-mary-icon name="o-photo" class="mx-auto mb-2 h-10 w-10 text-gray-400" />
                                <p class="text-sm text-gray-500">Seret & lepas atau <span
                                        class="font-medium text-[#0046FF]">klik untuk memilih</span></p>
                            </div>
                            <x-mary-input label="Catatan Foto (Opsional)" wire:model="photoNotes" />
                            <div class="flex justify-end">
                                <x-mary-button label="Unggah Foto" icon="o-cloud-arrow-up" type="submit"
                                    spinner="uploadPhoto" class="btn-primary btn-sm text-white"
                                    @disabled="empty($photoFiles)" />
                            </div>
                        </form>
                    </div>

                    <div class="{{ $cardClass }} p-5">
                        <h4 class="mb-1 text-sm font-bold text-black">Dokumen Bertanda Tangan</h4>
                        <p class="mb-4 text-xs text-gray-500">Unggah scan BAST, kuitansi (PDF/JPG/PNG, maks 10MB).</p>
                        <form wire:submit="uploadDocument" class="space-y-3">
                            <div x-data="{ isDragging: false }" x-on:dragover.prevent="isDragging = true"
                                x-on:dragleave="isDragging = false" x-on:drop.prevent="isDragging = false"
                                :class="isDragging ? 'border-[#FF8040] bg-[#FF8040]/5' :
                                    'border-gray-300 bg-gray-50 hover:border-[#FF8040]/50'"
                                class="relative rounded-xl border-2 border-dashed p-6 text-center transition-colors">
                                <input type="file" multiple accept="application/pdf,image/jpeg,image/png"
                                    wire:model="documentFiles"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" />
                                <x-mary-icon name="o-document-text" class="mx-auto mb-2 h-10 w-10 text-gray-400" />
                                <p class="text-sm text-gray-500">Seret & lepas atau <span
                                        class="font-medium text-[#FF8040]">klik untuk memilih</span></p>
                            </div>
                            <x-mary-input label="Catatan Dokumen (Opsional)" wire:model="documentNotes" />
                            <div class="flex justify-end">
                                <x-mary-button label="Unggah Dokumen" icon="o-cloud-arrow-up" type="submit"
                                    spinner="uploadDocument"
                                    class="btn-sm border-none bg-[#FF8040] text-white hover:bg-[#FF8040]/90"
                                    @disabled="empty($documentFiles)" />
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        <div class="{{ $isSchool && $hasSchoolLeftSection ? 'xl:col-span-1' : 'xl:col-span-3' }}">
            <div class="{{ $cardClass }} h-full p-5">
                <h4 class="mb-5 font-bold text-gray-900">Riwayat Penerimaan</h4>
                <ol class="relative ml-2 space-y-5 border-l border-gray-200">
                    @forelse ($timeline as $event)
                        <li class="relative pl-5">
                            <span
                                class="bg-primary absolute -left-1.5 top-1 h-3 w-3 rounded-full ring-4 ring-white"></span>
                            <p class="text-xs font-medium text-gray-400">{{ $event['time'] }}</p>
                            <p class="mt-0.5 text-sm font-bold text-gray-900">{{ $event['title'] }}</p>
                            @if ($event['desc'])
                                <p class="mt-0.5 text-xs text-gray-500">{{ $event['desc'] }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="pl-5 text-sm text-gray-500">Belum ada riwayat penerimaan.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>

    {{-- ─── SECTION 4: Daftar File Tersimpan & Tombol Konfirmasi ─── --}}
    @if ($totalFiles > 0)
        <div class="{{ $cardClass }} p-5">
            <h4 class="mb-4 text-sm font-bold text-black">File Bukti Penerimaan ({{ $totalFiles }})</h4>
            @if ($photos->isNotEmpty())
                <div class="mb-5">
                    <h5 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Foto Barang
                        ({{ $photos->count() }})</h5>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($photos as $photo)
                            <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                                <a href="{{ $getFileUrl($photo->file_path) }}" target="_blank"
                                    class="block aspect-square">
                                    <img src="{{ $getFileUrl($photo->file_path) }}" alt="Foto barang"
                                        class="h-full w-full object-cover transition-opacity hover:opacity-90" />
                                </a>
                                @if ($isSchool && !$isVerified)
                                    <button wire:click="openDeleteModal({{ $photo->id }})"
                                        class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white opacity-0 shadow-sm transition-opacity hover:bg-red-600 group-hover:opacity-100">
                                        <x-mary-icon name="o-trash" class="h-3.5 w-3.5" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($documents->isNotEmpty())
                <div>
                    <h5 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Dokumen
                        ({{ $documents->count() }})</h5>
                    <div class="space-y-2">
                        @foreach ($documents as $doc)
                            <div class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <x-mary-icon name="o-document" class="h-5 w-5 text-[#FF8040]" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $getFileUrl($doc->file_path) }}" target="_blank"
                                        class="block truncate text-sm font-medium text-black underline hover:text-[#0046FF]">
                                        {{ basename($doc->file_path) }}
                                    </a>
                                </div>
                                @if ($isSchool && !$isVerified)
                                    <button wire:click="openDeleteModal({{ $doc->id }})"
                                        class="p-1 text-gray-400 hover:text-red-500">
                                        <x-mary-icon name="o-trash" class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if ($isSchool && !$isVerified && $totalFiles > 0)
        <div class="{{ $cardClass }} flex items-center justify-between p-5">
            <div>
                <h4 class="text-sm font-bold text-black">Konfirmasi Penerimaan Barang</h4>
                <p class="mt-1 text-xs text-gray-500">
                    @if ($hasSignedDoc)
                        Dokumen bertanda tangan tersedia. Klik tombol untuk mengonfirmasi.
                    @else
                        <span class="text-red-500">Unggah minimal 1 dokumen bertanda tangan terlebih dahulu.</span>
                    @endif
                </p>
            </div>
            <x-mary-button label="Tandai Sudah Diterima" icon="o-check-circle" wire:click="openConfirmModal"
                class="{{ $hasSignedDoc ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-none' : 'bg-gray-200 text-gray-400 border-none cursor-not-allowed' }}"
                @disabled="!$hasSignedDoc" />
        </div>
    @endif

    <x-mary-modal wire:model="showDeleteModal" title="Hapus File" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <p class="text-sm text-gray-600">Apakah Anda yakin ingin menghapus file ini?</p>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showDeleteModal=false" class="btn-ghost text-black" />
            <x-mary-button label="Hapus" wire:click="confirmDelete" spinner="confirmDelete"
                class="border-none bg-[#FF8040] text-white" />
        </x-slot:actions>
    </x-mary-modal>

    <x-mary-modal wire:model="showConfirmModal" title="Konfirmasi Penerimaan" class="backdrop-blur"
        title-class="text-emerald-600">
        <p class="text-sm text-gray-600">Anda akan mengonfirmasi bahwa barang pengadaan telah diterima oleh sekolah.
        </p>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showConfirmModal=false" class="btn-ghost text-black" />
            <x-mary-button label="Ya, Konfirmasi" wire:click="confirmMarkVerified" spinner="confirmMarkVerified"
                class="border-none bg-emerald-500 text-white hover:bg-emerald-600" />
        </x-slot:actions>
    </x-mary-modal>

</div>
