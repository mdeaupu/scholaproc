<div>
    <x-mary-header title="Manajemen Tahun Anggaran" subtitle="Master periode tanggal pengadaan">
        <x-slot:actions>
            <x-mary-button icon="o-plus" class="btn-primary" wire:click="openModal" label="Tambah Tahun" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$budgetYears" with-pagination>

            @scope('cell_start_date', $year)
                {{ $year->start_date->format('d/m/Y') }}
            @endscope

            @scope('cell_end_date', $year)
                {{ $year->end_date->format('d/m/Y') }}
            @endscope

            @scope('cell_is_active', $year)
                <x-mary-badge :value="$year->is_active ? 'Tahun Berjalan' : 'Tidak Aktif'" class="{{ $year->is_active ? 'badge-primary' : 'badge-ghost' }}" />
            @endscope

            @scope('cell_actions', $year)
                <div class="flex space-x-2">
                    <x-mary-button icon="o-pencil" class="btn-sm btn-ghost" wire:click="openModal({{ $year->id }})" />

                    @if (!$year->is_active)
                        <x-mary-button icon="o-check-circle" class="btn-sm btn-ghost text-success"
                            tooltip="Jadikan Tahun Berjalan" wire:click="activateYear({{ $year->id }})"
                            wire:confirm="Aktifkan tahun ini? Tahun anggaran lain akan otomatis dinonaktifkan." />
                    @endif

                    <x-mary-button icon="o-trash" class="btn-sm btn-ghost text-error"
                        wire:click="delete({{ $year->id }})" wire:confirm="Yakin ingin menghapus tahun anggaran ini?" />
                </div>
            @endscope

        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="modal" title="{{ $budgetYear ? 'Edit Tahun Anggaran' : 'Tambah Tahun Anggaran' }}">
        <x-mary-form wire:submit="save">
            <x-mary-input label="Label Periode (Mis: 2025/2026)" wire:model="name" required />

            <div class="grid grid-cols-2 gap-4">
                <x-mary-input type="date" label="Tanggal Mulai" wire:model="start_date" required />
                <x-mary-input type="date" label="Tanggal Selesai" wire:model="end_date" required />
            </div>

            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" />
                <x-mary-button label="Simpan" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
