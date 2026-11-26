<div>
    <x-mary-header title="Manajemen Aturan Pajak" subtitle="Kelola aturan PPN, PPh 22, dan PPh 23 untuk evaluasi pajak transaksi." />

    <x-mary-card shadow class="mb-6">
        <div class="flex items-start gap-3 mb-4">
            <span class="shrink-0 w-10 h-10 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-shield-check" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
                <h3 class="font-bold text-gray-900">Kelengkapan Aturan Wajib (PPN · PPh 22 · PPh 23)</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sel hijau berarti satu aturan aktif siap pakai; sel oranye perlu ditindaklanjuti sebelum evaluasi pajak transaksi.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="text-left text-xs uppercase tracking-wider text-gray-400">Anak Perusahaan</th>
                        <th class="text-left text-xs uppercase tracking-wider text-gray-400">PPN</th>
                        <th class="text-left text-xs uppercase tracking-wider text-gray-400">PPh 22</th>
                        <th class="text-left text-xs uppercase tracking-wider text-gray-400">PPh 23</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coverageRows as $coverageRow)
                        <tr wire:key="coverage-{{ $loop->index }}">
                            <td class="text-sm font-semibold text-gray-700 whitespace-nowrap py-2 pr-6">{{ $coverageRow['label'] }}</td>
                            @foreach ($coverageRow['cells'] as $coverageCell)
                                <td class="py-2 pr-6">
                                    @if ($coverageCell['state'] === 'ok')
                                        <span class="badge badge-sm border-none font-semibold bg-green-100 text-green-700 whitespace-nowrap">{{ $coverageCell['label'] }}</span>
                                    @elseif ($coverageCell['state'] === 'missing')
                                        <span class="badge badge-sm border-none font-semibold bg-red-100 text-red-600 whitespace-nowrap">{{ $coverageCell['label'] }}</span>
                                    @else
                                        <span class="badge badge-sm border-none font-semibold bg-amber-100 text-amber-700 whitespace-nowrap">{{ $coverageCell['label'] }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama, dasar hukum, atau tipe transaksi..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-80 lg:w-[26rem]" />
            <x-mary-select wire:model.live="filterType" :options="[
                ['id' => '', 'name' => 'Semua Tipe'],
                ['id' => 'ppn', 'name' => 'PPN'],
                ['id' => 'pph22', 'name' => 'PPh 22'],
                ['id' => 'pph23', 'name' => 'PPh 23'],
            ]" class="w-full sm:w-40" />
            <x-mary-select wire:model.live="filterStatus" :options="[
                ['id' => '', 'name' => 'Semua'],
                ['id' => 'active', 'name' => 'Aktif'],
                ['id' => 'inactive', 'name' => 'Nonaktif'],
            ]" class="w-full sm:w-44" />
            <x-mary-select wire:model.live="filterSubsidiary" :options="$filterSubsidiaryOptions" class="w-full sm:w-56" />
            <x-mary-button label="Tambah Aturan Pajak" icon="o-plus" wire:click="openModal"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full sm:w-auto sm:ml-auto" />
        </div>
    </x-mary-card>

    <x-mary-card title="Daftar Aturan Pajak" separator shadow>
        <x-mary-table :headers="$headers" :rows="$taxRules" container-class="overflow-visible" class="w-full">
            @scope('cell_row_no', $rule)
                <span class="text-sm text-gray-400">{{ $rule->row_no }}</span>
            @endscope
            @scope('cell_tax_type', $rule)
                <span @class([
                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    $typeClasses[$rule->tax_type] ?? 'bg-gray-100 text-gray-600',
                ])>
                    {{ $typeLabels[$rule->tax_type] ?? $rule->tax_type }}
                </span>
            @endscope
            @scope('cell_name', $rule)
                <div class="font-semibold text-sm text-gray-800">{{ $rule->name }}</div>
                <div class="text-xs text-gray-400 mt-0.5">v{{ $rule->version }} · {{ $rule->transaction_type ?? 'Semua tipe transaksi' }} · {{ $rule->funding_source ?? 'Semua sumber dana' }}</div>
            @endscope
            @scope('cell_entity', $rule)
                @if ($rule->subsidiary_id === null)
                    <span class="text-sm text-gray-500">Semua entitas</span>
                @else
                    <span class="text-sm text-gray-700">{{ $rule->subsidiary?->name ?? '-' }}</span>
                @endif
            @endscope
            @scope('cell_rate', $rule)
                <span class="font-semibold text-sm text-gray-800 whitespace-nowrap">{{ (float) $rule->rate }}%</span>
            @endscope
            @scope('cell_status', $rule)
                <span @class([
                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    $statusClasses[$rule->status] ?? 'bg-gray-100 text-gray-600',
                ])>
                    {{ $statusLabels[$rule->status] ?? $rule->status }}
                </span>
                <div class="text-xs text-gray-400 mt-1">Dibuat: {{ $userNames[$rule->created_by] ?? '-' }}</div>
                @if ($rule->verified_by !== null)
                    <div class="text-xs text-gray-400">Diaktifkan: {{ $userNames[$rule->verified_by] ?? '-' }}</div>
                @endif
            @endscope
            @scope('cell_actions', $rule)
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button"
                                class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF] hover:bg-base-200"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>

                        <x-mary-menu-item title="Edit Aturan" icon="o-pencil-square" class="cursor-pointer"
                            wire:click="openModal({{ $rule->id }})" />
                        @if ($rule->status === \App\Models\TaxRule::STATUS_ACTIVE)
                            <x-mary-menu-item title="Nonaktifkan" icon="o-x-circle" class="cursor-pointer"
                                wire:click="deactivate({{ $rule->id }})"
                                wire:confirm="Nonaktifkan aturan pajak ini? Evaluasi transaksi tidak akan memakai aturan ini." />
                        @else
                            <x-mary-menu-item title="Aktifkan" icon="o-check-circle" class="cursor-pointer"
                                wire:click="activate({{ $rule->id }})"
                                wire:confirm="Aktifkan aturan pajak ini?" />
                        @endif
                        <x-mary-menu-item title="Hapus" icon="o-trash" class="cursor-pointer text-red-500"
                            wire:click="delete({{ $rule->id }})"
                            wire:confirm="Yakin ingin menghapus aturan pajak ini?" />
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($taxRules->total() > 0)
            <div class="mt-4">{{ $taxRules->links('pagination.table', ['label' => 'aturan pajak']) }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="modal" :title="$taxRule?->exists ? 'Edit Aturan Pajak' : 'Tambah Aturan Pajak'"
        class="backdrop-blur text-black" box-class="max-w-3xl" title-class="text-[#0046FF]">
        <x-mary-form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-mary-select label="Tipe Pajak" wire:model="tax_type" placeholder="Pilih tipe pajak" required :options="[
                    ['id' => 'ppn', 'name' => 'PPN'],
                    ['id' => 'pph22', 'name' => 'PPh 22'],
                    ['id' => 'pph23', 'name' => 'PPh 23'],
                ]" />
                <x-mary-input label="Nama Aturan" wire:model="name" maxlength="255" required
                    placeholder="Contoh: PPN jasa konsultasi 11%" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-mary-select label="Anak Perusahaan" wire:model="subsidiary_id" :options="$subsidiaryOptions" />
                <x-mary-select label="Tipe Transaksi" wire:model="transaction_type" :options="$categoryOptions" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-mary-select label="Sumber Dana" wire:model="funding_source" :options="$fundingOptions" />
                <x-mary-input type="number" label="Tarif (%)" wire:model="rate" step="0.01" min="0" max="100"
                    required />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-mary-input type="date" label="Berlaku Mulai" wire:model="effective_from" />
                <x-mary-input type="date" label="Berlaku Sampai" wire:model="effective_to" />
            </div>
            <x-mary-textarea label="Dasar Hukum" wire:model="legal_basis" rows="3" required
                placeholder="Contoh: PMK 131/PMK.010/2024 tentang PPN" />
            <x-mary-textarea label="Catatan Perubahan" wire:model="change_notes" rows="2"
                placeholder="Opsional; dicatat pada riwayat aturan." />
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="is_active" class="toggle toggle-success" />
                <span class="text-sm font-medium text-gray-700">Aturan aktif dan siap dipakai evaluasi transaksi</span>
            </label>
            <x-slot:actions>
                <x-mary-button label="Batal" wire:click="$set('modal', false)" class="btn-ghost" />
                <x-mary-button label="Simpan" icon="o-check" type="submit" spinner="save"
                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
