<div>
    <x-mary-header title="Manajemen Tahun Anggaran" subtitle="Kelola periode anggaran dan rentang tanggal pengadaan." />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span
                class="shrink-0 w-12 h-12 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-calendar-days" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Total Tahun Anggaran</p>
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
                <p class="text-sm text-gray-500">Tahun Aktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $stats['active'] }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                <x-mary-icon name="o-x-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Tahun Nonaktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">
                    {{ $stats['inactive'] }}</p>
            </div>
        </div>
    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari tahun atau tanggal..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-80 lg:w-[28rem]" />
            <x-mary-select wire:model.live="filterStatus" :options="[
                ['id' => '', 'name' => 'Semua'],
                ['id' => 'active', 'name' => 'Aktif'],
                ['id' => 'inactive', 'name' => 'Nonaktif'],
            ]" class="w-full sm:w-44" />
            <x-mary-button label="Tambah Tahun Anggaran" icon="o-plus" wire:click="openModal"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full sm:w-auto sm:ml-auto" />
        </div>
    </x-mary-card>

    <x-mary-card title="Daftar Tahun Anggaran" separator shadow>
        <x-mary-table :headers="$headers" :rows="$budgetYears" container-class="overflow-visible" class="w-full">
            @scope('cell_row_no', $year)
                <span class="text-sm text-gray-400">{{ $year->row_no }}</span>
            @endscope
            @scope('cell_name', $year)
                <span class="font-mono text-sm font-semibold text-gray-700">{{ $year->name }}</span>
            @endscope
            @scope('cell_start_date', $year)
                <span class="text-sm text-gray-600 whitespace-nowrap">{{ $year->start_date->format('d M Y') }}</span>
            @endscope
            @scope('cell_end_date', $year)
                <span class="text-sm text-gray-600 whitespace-nowrap">{{ $year->end_date->format('d M Y') }}</span>
            @endscope
            @scope('cell_is_active', $year)
                @if ($year->is_active)
                    <x-mary-badge value="Aktif" class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                @else
                    <x-mary-badge value="Nonaktif" class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                @endif
            @endscope
            @scope('cell_actions', $year)
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button"
                                class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF] hover:bg-base-200"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>

                        <x-mary-menu-item title="Edit Tahun Anggaran" icon="o-pencil-square" class="cursor-pointer"
                            wire:click="openModal({{ $year->id }})" />
                        @if (!$year->is_active)
                            <x-mary-menu-item title="Jadikan Aktif" icon="o-check-circle" class="cursor-pointer"
                                wire:click="activateYear({{ $year->id }})"
                                wire:confirm="Aktifkan tahun ini? Tahun anggaran lain akan otomatis dinonaktifkan." />
                        @endif
                        <x-mary-menu-item title="Hapus" icon="o-trash" class="cursor-pointer text-red-500"
                            wire:click="delete({{ $year->id }})"
                            wire:confirm="Yakin ingin menghapus tahun anggaran ini?" />
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($budgetYears->total() > 0)
            <div class="mt-4">{{ $budgetYears->links('pagination.table', ['label' => 'tahun anggaran']) }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="modal" :title="$budgetYear?->exists ? 'Edit Tahun Anggaran' : 'Tambah Tahun Anggaran'" class="backdrop-blur text-black" box-class="max-w-2xl"
        title-class="text-[#0046FF]">
        <x-mary-form wire:submit="save" class="space-y-4">
            <x-mary-input label="Tahun Anggaran" wire:model="tahun" maxlength="20" required />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-mary-input type="date" label="Tanggal Mulai" wire:model="tanggal_mulai" required />
                <x-mary-input type="date" label="Tanggal Selesai" wire:model="tanggal_selesai" required />
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="is_active" class="toggle toggle-success" />
                <span class="text-sm font-medium text-gray-700">Jadikan tahun anggaran aktif</span>
            </label>
            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" class="btn-ghost" />
                <x-mary-button label="Simpan" icon="o-check" type="submit" spinner="save"
                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
