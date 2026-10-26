<div>
    <x-mary-header title="Manajemen Satuan Barang"
        subtitle="Kelola satuan yang digunakan pada detail barang pengadaan." />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span
                class="shrink-0 w-12 h-12 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-scale" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Total Satuan</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $stats['total'] }}
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $stats['active'] }} aktif | {{ $stats['inactive'] }} nonaktif
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <x-mary-icon name="o-check-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Satuan Aktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $stats['active'] }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                <x-mary-icon name="o-x-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Satuan Nonaktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">
                    {{ $stats['inactive'] }}</p>
            </div>
        </div>
    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama satuan..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-80 lg:w-[28rem]" />
            <x-mary-select wire:model.live="filterStatus" :options="[
                ['id' => '', 'name' => 'Semua'],
                ['id' => 'active', 'name' => 'Aktif'],
                ['id' => 'inactive', 'name' => 'Nonaktif'],
            ]" class="w-full sm:w-44" />
            <x-mary-button label="Tambah Satuan Barang" icon="o-plus" wire:click="openModal"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full sm:w-auto sm:ml-auto" />
        </div>
    </x-mary-card>

    <x-mary-card title="Daftar Satuan Barang" separator shadow>
        <x-mary-table :headers="$headers" :rows="$units" container-class="overflow-visible" class="w-full">
            @scope('cell_row_no', $unit)
                <span class="text-sm text-gray-400">{{ $unit->row_no }}</span>
            @endscope
            @scope('cell_name', $unit)
                <span class="text-sm font-medium text-gray-800">{{ $unit->name }}</span>
            @endscope
            @scope('cell_is_active', $unit)
                @if ($unit->is_active)
                    <x-mary-badge value="Aktif" class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                @else
                    <x-mary-badge value="Nonaktif" class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                @endif
            @endscope
            @scope('cell_actions', $unit)
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button"
                                class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF] hover:bg-base-200"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>

                        <x-mary-menu-item title="Edit Satuan" icon="o-pencil-square" class="cursor-pointer"
                            wire:click="openModal({{ $unit->id }})" />
                        <x-mary-menu-item :title="$unit->is_active ? 'Nonaktifkan' : 'Aktifkan'" :icon="$unit->is_active ? 'o-eye-slash' : 'o-eye'" class="cursor-pointer"
                            wire:click="toggleActive({{ $unit->id }})" />
                        <x-mary-menu-item title="Hapus" icon="o-trash" class="cursor-pointer text-red-500"
                            wire:click="delete({{ $unit->id }})" wire:confirm="Yakin ingin menghapus satuan ini?" />
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($units->total() > 0)
            <div class="mt-4">{{ $units->links('pagination.table', ['label' => 'satuan']) }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="modal" :title="$itemUnit?->exists ? 'Edit Satuan Barang' : 'Tambah Satuan Barang'" class="backdrop-blur text-black" box-class="max-w-2xl"
        title-class="text-[#0046FF]">
        <x-mary-form wire:submit="save" class="space-y-4">
            <x-mary-input label="Nama Satuan" wire:model="nama_satuan" placeholder="Misal: Pcs, Box, Kg, Liter" required
                maxlength="50" />
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="is_active" class="toggle toggle-success" />
                <span class="text-sm font-medium text-gray-700">Satuan aktif</span>
            </label>
            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" class="btn-ghost" />
                <x-mary-button label="Simpan" icon="o-check" type="submit" spinner="save"
                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
