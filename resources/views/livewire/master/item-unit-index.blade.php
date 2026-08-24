<div>
    <x-mary-header title="Manajemen Satuan Barang" subtitle="Master data satuan item pengadaan">
        <x-slot:actions>
            <x-mary-button icon="o-plus" class="btn-primary" wire:click="openModal" label="Tambah Satuan" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$units" with-pagination>

            @scope('cell_is_active', $unit)
                <x-mary-badge :value="$unit->is_active ? 'Aktif' : 'Nonaktif'" class="{{ $unit->is_active ? 'badge-success' : 'badge-error' }}" />
            @endscope

            @scope('cell_actions', $unit)
                <div class="flex space-x-2">
                    <x-mary-button icon="o-pencil" class="btn-sm btn-ghost" wire:click="openModal({{ $unit->id }})" />

                    <x-mary-button icon="{{ $unit->is_active ? 'o-eye-slash' : 'o-eye' }}" class="btn-sm btn-ghost"
                        wire:click="toggleActive({{ $unit->id }})" />

                    <x-mary-button icon="o-trash" class="btn-sm btn-ghost text-error"
                        wire:click="delete({{ $unit->id }})" wire:confirm="Yakin ingin menghapus satuan ini?" />
                </div>
            @endscope

        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="modal" title="{{ $itemUnit ? 'Edit Satuan' : 'Tambah Satuan' }}">
        <x-mary-form wire:submit="save">
            <x-mary-input label="Nama Satuan (Misal: Pcs, Rim, Unit)" wire:model="name" required autofocus />
            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" />
                <x-mary-button label="Simpan" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
