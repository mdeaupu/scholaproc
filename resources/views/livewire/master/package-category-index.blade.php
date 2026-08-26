<div>
    <x-mary-header title="Manajemen Kategori Paket" subtitle="Master data referensi pengadaan">
        <x-slot:actions>
            <x-mary-button icon="o-plus" class="btn-primary" wire:click="openModal" label="Tambah Data" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$categories" with-pagination>

            @scope('cell_is_active', $category)
                <x-mary-badge :value="$category->is_active ? 'Aktif' : 'Nonaktif'" class="{{ $category->is_active ? 'badge-success' : 'badge-error' }}" />
            @endscope

            @scope('cell_actions', $category)
                <div class="flex space-x-2">
                    <x-mary-button icon="o-pencil" class="btn-sm btn-ghost" wire:click="openModal({{ $category->id }})" />

                    <x-mary-button icon="{{ $category->is_active ? 'o-eye-slash' : 'o-eye' }}" class="btn-sm btn-ghost"
                        wire:click="toggleActive({{ $category->id }})" />

                    <x-mary-button icon="o-trash" class="btn-sm btn-ghost text-error"
                        wire:click="delete({{ $category->id }})" wire:confirm="Yakin ingin menghapus data ini?" />
                </div>
            @endscope

        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="modal" title="{{ $category ? 'Edit Kategori' : 'Tambah Kategori' }}">
        <x-mary-form wire:submit="save">
            <x-mary-input label="Nama Kategori" wire:model="name" required autofocus />
            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" />
                <x-mary-button label="Simpan" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
