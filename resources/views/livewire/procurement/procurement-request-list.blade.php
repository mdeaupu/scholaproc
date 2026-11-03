<div>
    <x-mary-header title="Daftar Pengajuan"
        subtitle="Berikut adalah daftar riwayat pengajuan pengadaan barang sekolah Anda." />

    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        @php
            $cards = [
                ['label' => 'Total Pengajuan', 'value' => $stats['total'], 'icon' => 'o-document-text', 'color' => 'blue'],
                ['label' => 'Draft', 'value' => $stats['draft'], 'icon' => 'o-clock', 'color' => 'gray'],
                ['label' => 'Submitted', 'value' => $stats['submitted'], 'icon' => 'o-paper-airplane', 'color' => 'blue'],
                ['label' => 'Needs Revision', 'value' => $stats['needs_revision'], 'icon' => 'o-arrow-path', 'color' => 'red'],
                ['label' => 'Price Agreed', 'value' => $stats['price_agreed'], 'icon' => 'o-hand-thumb-up', 'color' => 'violet'],
                ['label' => 'Selesai', 'value' => $stats['completed'], 'icon' => 'o-check-circle', 'color' => 'green'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <span @class([
                    'shrink-0 w-12 h-12 rounded-full flex items-center justify-center',
                    'bg-[#0046FF]/10 text-[#0046FF]' => $card['color'] === 'blue',
                    'bg-gray-100 text-gray-600' => $card['color'] === 'gray',
                    'bg-red-100 text-red-500' => $card['color'] === 'red',
                    'bg-violet-100 text-violet-600' => $card['color'] === 'violet',
                    'bg-green-100 text-green-600' => $card['color'] === 'green',
                ])>
                    <x-mary-icon :name="$card['icon']" class="w-6 h-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">
                        {{ $card['value'] }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Pengajuan</p>
                </div>
            </div>
        @endforeach
    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari kategori atau nomor pengajuan..."
                wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" clearable
                class="w-full sm:w-72 lg:w-80" />
            <x-mary-select wire:model.live="filterStatus" :options="collect($statusOptions)
                ->map(fn ($label, $id) => ['id' => $id, 'name' => $label])
                ->prepend(['id' => '', 'name' => 'Semua Status'])
                ->values()
                ->all()" class="w-full sm:w-44" />
            <x-mary-select wire:model.live="filterBudgetYear" :options="$budgetYears
                ->map(fn ($year) => ['id' => $year->id, 'name' => $year->name])
                ->prepend(['id' => '', 'name' => 'Semua Tahun'])
                ->values()
                ->all()" class="w-full sm:w-44" />
            <x-mary-select wire:model.live="filterFundingSource" :options="$fundingSources
                ->map(fn ($source) => ['id' => $source->id, 'name' => $source->name])
                ->prepend(['id' => '', 'name' => 'Semua Sumber Dana'])
                ->values()
                ->all()" class="w-full sm:w-52" />
            @if (auth()->user()->isSchool())
                <x-mary-button label="Ajukan Pengadaan" link="{{ route('procurement.create') }}" icon="o-plus"
                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none ml-auto" wire:navigate />
            @endif
        </div>
    </x-mary-card>

    @php
        $procurementHeaders = [
            ['key' => 'no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'number', 'label' => 'Nomor Pengajuan'],
            ['key' => 'created_at', 'label' => 'Tanggal Pengajuan'],
            ['key' => 'packageCategory.name', 'label' => 'Kategori'],
            ['key' => 'fundingSource.name', 'label' => 'Sumber Dana'],
            ['key' => 'budgetYear.name', 'label' => 'Tahun Anggaran'],
            ['key' => 'total_value', 'label' => 'Total Nilai'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'w-14 text-right', 'sortable' => false],
        ];
    @endphp

    <x-mary-card title="Daftar Pengajuan" separator shadow>
        <x-mary-table :headers="$procurementHeaders" :rows="$requests" container-class="overflow-visible"
            class="table-fixed w-full">
            @scope('cell_no', $request)
                <span class="text-sm text-gray-400">{{ $request->id }}</span>
            @endscope
            @scope('cell_number', $request)
                <span class="font-mono text-sm font-semibold text-[#0046FF]">
                    PGN-{{ $request->created_at->format('Y') }}-{{ str_pad((string) $request->id, 4, '0', STR_PAD_LEFT) }}
                </span>
            @endscope
            @scope('cell_created_at', $request)
                <span class="text-sm text-gray-600 whitespace-nowrap">
                    {{ $request->created_at->locale('id')->isoFormat('D MMM YYYY') }}
                </span>
            @endscope
            @scope('cell_packageCategory.name', $request)
                <span class="text-sm text-gray-700">{{ $request->packageCategory?->name ?? '-' }}</span>
            @endscope
            @scope('cell_fundingSource.name', $request)
                <span class="text-sm text-gray-700">{{ $request->fundingSource?->name ?? '-' }}</span>
            @endscope
            @scope('cell_budgetYear.name', $request)
                <span class="text-sm text-gray-700">{{ $request->budgetYear?->name ?? '-' }}</span>
            @endscope
            @scope('cell_total_value', $request)
                <span class="font-semibold text-[#0046FF] whitespace-nowrap">
                    Rp {{ number_format((float) ($request->estimated_subtotal ?? 0), 0, ',', '.') }}
                </span>
            @endscope
            @scope('cell_status', $request)
                <span @class([
                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                    \App\Models\ProcurementRequest::STATUS_PILL_CLASSES[$request->status] ?? 'bg-gray-100 text-gray-700',
                ])>
                    {{ $statusOptions[$request->status] ?? \Illuminate\Support\Str::headline($request->status) }}
                </span>
            @endscope
            @scope('cell_actions', $request)
                @php
                    $editable = auth()->user()->isSchool()
                        && in_array($request->status, [
                            \App\Models\ProcurementRequest::STATUS_DRAFT,
                            \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION,
                        ], true);
                    $deletable = auth()->user()->isSchool()
                        && $request->status === \App\Models\ProcurementRequest::STATUS_DRAFT;
                @endphp
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button"
                                class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF] hover:bg-base-200"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>

                        <x-mary-menu-item title="Detail" icon="o-eye" class="cursor-pointer"
                            link="{{ route('procurement.show', $request->id) }}" wire:navigate />
                        @if ($editable)
                            <x-mary-menu-item title="Edit Pengajuan" icon="o-pencil-square" class="cursor-pointer"
                                link="{{ route('procurement.edit', $request->id) }}" wire:navigate />
                            @if ($deletable)
                                <x-mary-menu-item title="Hapus Pengajuan" icon="o-trash"
                                    class="cursor-pointer text-red-500"
                                    wire:click="confirmDeleteDraft({{ $request->id }})" />
                            @endif
                        @endif
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($requests->total() > 0)
            <div>{{ $requests->links('pagination.table') }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="confirmingDeleteDraft" title="Hapus Pengajuan" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="shrink-0 rounded-full bg-[#FF8040]/10 p-3 text-[#FF8040]">
                <x-mary-icon name="o-trash" class="h-7 w-7" />
            </div>
            <div>
                <p class="font-semibold text-black">
                    Hapus <span class="text-[#FF8040]">"{{ $targetDraftNumber }}"</span>?
                </p>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    Draf pengajuan dan rincian barangnya akan dihapus dari tampilan, tetapi tetap disimpan sebagai
                    riwayat dan tidak dapat dipulihkan langsung.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDeleteDraft', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="deleteConfirmed" wire:loading.attr="disabled"
                spinner="deleteConfirmed" class="border-none bg-[#FF8040] text-white" />
        </x-slot:actions>
    </x-mary-modal>
</div>
