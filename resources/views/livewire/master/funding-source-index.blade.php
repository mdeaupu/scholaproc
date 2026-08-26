<div>
    <x-mary-header title="Manajemen Sumber Dana" subtitle="Master data sumber dana sekolah">
        <x-slot:actions>
            <x-mary-button icon="o-plus" class="btn-primary" wire:click="openModal" label="Tambah Data" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$sources" with-pagination>

            @scope('cell_is_active', $source)
                <x-mary-badge :value="$source->is_active ? 'Aktif' : 'Nonaktif'" class="{{ $source->is_active ? 'badge-success' : 'badge-error' }}" />
            @endscope

            @scope('cell_actions', $source)
                <div class="flex space-x-2">
                    <x-mary-button icon="o-pencil" class="btn-sm btn-ghost" wire:click="openModal({{ $source->id }})" />

                    <x-mary-button icon="{{ $source->is_active ? 'o-eye-slash' : 'o-eye' }}" class="btn-sm btn-ghost"
                        wire:click="toggleActive({{ $source->id }})" />

                    <x-mary-button icon="o-trash" class="btn-sm btn-ghost text-error"
                        wire:click="delete({{ $source->id }})" wire:confirm="Yakin ingin menghapus sumber dana ini?" />
                </div>
            @endscope

        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="modal" title="{{ $fundingSource ? 'Edit Sumber Dana' : 'Tambah Sumber Dana' }}">
        <x-mary-form wire:submit="save">
            <x-mary-input label="Nama Sumber Dana (Misal: BOSP Reguler)" wire:model="name" required autofocus />
            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" />
                <x-mary-button label="Simpan" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
