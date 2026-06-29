<div>
    <x-mary-header title="Pengajuan Pengadaan" subtitle="Daftar seluruh permohonan pengadaan barang/jasa" separator>
        <x-slot:actions>
            @can('admin-school-only')
                <x-mary-button icon="o-plus" class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                    link="{{ route('procurement.create') }}" wire:navigate>
                    Buat Pengajuan
                </x-mary-button>
            @endcan
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari kategori atau sumber dana..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-64" />
            <x-mary-select wire:model.live="filterStatus" :options="[
                ['id' => 'draft', 'name' => 'Draft'],
                ['id' => 'submitted', 'name' => 'Diajukan (Submitted)'],
                ['id' => 'verified', 'name' => 'Diverifikasi'],
                ['id' => 'supplier_assigned', 'name' => 'Supplier Dipilih'],
                ['id' => 'items_prepared', 'name' => 'Barang Disiapkan'],
                ['id' => 'completed', 'name' => 'Selesai'],
                ['id' => 'rejected', 'name' => 'Ditolak'],
            ]" placeholder="Semua Status" class="w-52" />
        </div>
    </x-mary-card>
    <x-mary-card shadow class="border-t-4 border-[#0046FF]">
        <x-mary-table :headers="$headers" :rows="$requests" with-pagination>
            @scope('cell_items_count', $procurement)
                <span class="badge badge-ghost font-medium">{{ $procurement->items_count }} Item</span>
            @endscope
            @scope('cell_total_budget', $procurement)
                <span class="font-semibold text-[#0046FF]">Rp
                    {{ number_format($procurement->estimatedSubtotal(), 0, ',', '.') }}</span>
            @endscope
            @scope('cell_status', $procurement)
                @php
                    $badgeColor = match ($procurement->status) {
                        'draft' => 'bg-gray-200 text-gray-700 border-none',
                        'submitted', 'verified', 'items_prepared', 'completed' => 'bg-[#0046FF] text-white border-none',
                        'supplier_assigned', 'rejected' => 'bg-[#FF8040] text-white border-none',
                        default => 'badge-ghost',
                    };
                @endphp
                <x-mary-badge :value="Str::headline($procurement->status)" class="{{ $badgeColor }} badge-sm font-semibold" />
            @endscope
            @scope('actions', $procurement)
                <div class="flex gap-1 justify-end">
                    <x-mary-button icon="o-eye" link="{{ route('procurement.show', $procurement->id) }}"
                        class="btn-sm btn-ghost text-[#0046FF]" tooltip="Lihat Detail" wire:navigate />
                    @if ($procurement->status === 'draft' && auth()->user()->can('admin-school-only'))
                        <x-mary-button icon="o-pencil-square" link="{{ route('procurement.edit', $procurement->id) }}"
                            class="btn-sm btn-ghost text-black" tooltip="Edit Pengajuan" wire:navigate />
                        <x-mary-button icon="o-trash" wire:click="delete({{ $procurement->id }})"
                            wire:confirm="Apakah Anda yakin ingin menghapus draft pengajuan ini?"
                            class="btn-sm btn-ghost text-[#FF8040]" tooltip="Hapus Draft" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
