<div>
    <x-mary-header title="Manajemen Anak Perusahaan"
        subtitle="Kelola identitas hukum, legalitas, status, dan riwayat transaksi Anak Perusahaan." />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-building-office-2" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
            <p class="text-sm text-gray-500">Total Anak Perusahaan</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $totalSubsidiaries }}</p>
                <p class="text-xs text-gray-400 mt-1">Aktif: {{ $activeSubsidiaries }} | Nonaktif: {{ $inactiveSubsidiaries }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <x-mary-icon name="o-check-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Anak Perusahaan Aktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $activeSubsidiaries }}</p>
                <p class="text-xs text-gray-400 mt-1">Tersedia untuk transaksi baru</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                <x-mary-icon name="o-x-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Anak Perusahaan Nonaktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $inactiveSubsidiaries }}</p>
                <p class="text-xs text-gray-400 mt-1">Tidak tersedia untuk transaksi baru</p>
            </div>
        </div>
    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama, NPWP, NIB, atau alamat..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-72 lg:w-96" />
            <x-mary-select wire:model.live="filterStatus" :options="[
                ['id' => '', 'name' => 'Semua'],
                ['id' => 'active', 'name' => 'Aktif'],
                ['id' => 'inactive', 'name' => 'Nonaktif'],
            ]" class="w-44" />
            <x-mary-button label="Tambah Anak Perusahaan" link="{{ route('superadmin.subsidiaries.create') }}"
                icon="o-plus" class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none ml-auto"
                wire:navigate />
        </div>
    </x-mary-card>

    <x-mary-card title="Daftar Anak Perusahaan" separator shadow>
        <x-mary-table :headers="$headers" :rows="$subsidiaries" container-class="overflow-visible" class="table-fixed w-full">
            @scope('cell_no', $subsidiary)
                <span class="text-sm text-gray-400">{{ $subsidiary->row_no }}</span>
            @endscope
            @scope('cell_name', $subsidiary)
                <div class="flex items-center gap-2.5 min-w-0">
                    <span
                        class="shrink-0 w-7 h-7 rounded-lg bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                        <x-mary-icon name="o-building-library" class="w-4 h-4" />
                    </span>
                    <span class="font-semibold text-gray-900 truncate" title="{{ $subsidiary->name }}">
                        {{ $subsidiary->name }}
                    </span>
                </div>
            @endscope
            @scope('cell_npwp', $subsidiary)
                <span class="font-mono text-sm font-semibold text-gray-700">{{ $subsidiary->npwp }}</span>
            @endscope
            @scope('cell_director_name', $subsidiary)
                <span class="text-sm text-gray-700">{{ $subsidiary->director_name }}</span>
            @endscope
            @scope('cell_status', $subsidiary)
                @if ($subsidiary->isActive())
                    <x-mary-badge value="Aktif" class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                @else
                    <x-mary-badge value="Nonaktif" class="bg-gray-100 text-gray-600 border-none badge-sm font-semibold" />
                @endif
            @endscope
            @scope('cell_actions', $subsidiary)
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button"
                                class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF]"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>
                        <x-mary-menu-item title="Detail" icon="o-eye" class="cursor-pointer"
                            wire:click="show({{ $subsidiary->id }})" />
                        <x-mary-menu-item title="Edit data" icon="o-pencil-square"
                            link="{{ route('superadmin.subsidiaries.edit', $subsidiary->id) }}" />
                        <x-mary-menu-item title="Hapus" icon="o-trash" class="cursor-pointer text-red-500"
                            wire:click="confirmDestroy({{ $subsidiary->id }})" />
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($subsidiaries->total() > 0)
            <div>{{ $subsidiaries->links('pagination.table', ['label' => 'Anak Perusahaan']) }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="detailModal" title="Detail Anak Perusahaan"
        subtitle="Identitas hukum, legalitas, dan transaksi terkait." class="backdrop-blur text-black"
        box-class="max-w-5xl" title-class="text-[#0046FF]">
        @if ($selectedSubsidiary)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="rounded-xl border border-gray-100 bg-white p-5">
                    <h3 class="font-bold text-gray-900 mb-4">Identitas</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Nama</dt>
                            <dd class="font-semibold text-right">{{ $selectedSubsidiary->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">NPWP</dt>
                            <dd class="font-mono text-right">{{ $selectedSubsidiary->npwp }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Direktur</dt>
                            <dd class="text-right">{{ $selectedSubsidiary->director_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">NIB</dt>
                            <dd class="text-right">{{ $selectedSubsidiary->nib ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Alamat</dt>
                            <dd class="text-right">{{ $selectedSubsidiary->address ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Kontak</dt>
                            <dd class="text-right">{{ $selectedSubsidiary->phone ?: '-' }} ·
                                {{ $selectedSubsidiary->email ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Status</dt>
                            <dd>{{ $selectedSubsidiary->isActive() ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-5">
                    <h3 class="font-bold text-gray-900 mb-4">Dokumen Legalitas</h3>
                    @forelse ($selectedSubsidiary->legalDocuments as $document)
                        <div class="flex justify-between gap-3 py-2 border-b border-gray-100 text-sm">
                            <span>{{ $document->document_type->label() }}</span>
                            <span class="font-mono">{{ $document->document_number ?: '-' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada informasi legalitas tambahan.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-gray-100 bg-white p-5">
                <h3 class="font-bold text-gray-900 mb-3">Transaksi</h3>
                @forelse ($selectedSubsidiary->procurementRequests as $request)
                    <div class="flex flex-wrap justify-between gap-2 py-2 border-b border-gray-100 text-sm">
                        <span>{{ $request->packageCategory?->name ?? 'Pengadaan' }} —
                            {{ $request->school?->name ?? '-' }}</span>
                        <span>{{ $request->status_label }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada transaksi yang ditetapkan ke Anak Perusahaan ini.</p>
                @endforelse
            </div>
        @endif
    </x-mary-modal>

    <x-mary-modal wire:model="confirmingDestroy" title="Hapus Anak Perusahaan" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-trash" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">
                    Hapus <span class="text-[#FF8040]">"{{ $targetSubsidiaryName }}"</span>?
                </p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Penghapusan bersifat permanen dan hanya dapat dilakukan jika Anak Perusahaan belum digunakan
                    pada transaksi atau dokumen.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDestroy', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="destroy" class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
</div>
