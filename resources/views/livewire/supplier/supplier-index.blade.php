<div>
    <x-mary-header title="Manajemen Supplier"
        subtitle="Kelola data supplier, legalitas, dan riwayat proyek supplier." />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-truck" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Total Supplier</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $totalSuppliers }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    Terlibat proyek: {{ $suppliersWithProjects }} | Belum: {{ $totalSuppliers - $suppliersWithProjects }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <x-mary-icon name="o-check-badge" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Supplier Tersertifikasi</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $certifiedSuppliers }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($percentCertified, 1, ',', '.') }}% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                <x-mary-icon name="o-document-check" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Legalitas Lengkap</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $completeLegalSuppliers }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($percentCompleteLegal, 1, ',', '.') }}% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                <x-mary-icon name="o-clipboard-document-list" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Total Proyek</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $totalProjects }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    Aktif: {{ $activeProjects }} | Selesai: {{ $completedProjects }}</p>
            </div>
        </div>
    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama supplier, NPWP, atau alamat..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-72 lg:w-80" />
            <x-mary-select wire:model.live="filterLegalitas"
                :options="[['id' => '', 'name' => 'Semua'], ['id' => 'lengkap', 'name' => 'Legalitas Lengkap'], ['id' => 'belum', 'name' => 'Belum Lengkap']]"
                class="w-44" />
            @if (auth()->user()->isSuperAdmin())
                <x-mary-button label="Tambah Supplier" link="{{ route('superadmin.suppliers.create') }}"
                    icon="o-plus"
                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none ml-auto" wire:navigate />
            @endif
        </div>
    </x-mary-card>

    <x-mary-card title="Daftar Supplier" separator shadow>
        <x-mary-table :headers="$headers" :rows="$suppliers" container-class="overflow-visible"
            class="table-fixed w-full">
            @scope('cell_no', $supplier)
                <span class="text-sm text-gray-400">{{ $supplier->row_no }}</span>
            @endscope
            @scope('cell_company_name', $supplier)
                @php
                    $avatarColors = [
                        ['bg-blue-100', 'text-blue-600'],
                        ['bg-orange-100', 'text-orange-600'],
                        ['bg-emerald-100', 'text-emerald-600'],
                        ['bg-purple-100', 'text-purple-600'],
                        ['bg-cyan-100', 'text-cyan-600'],
                        ['bg-rose-100', 'text-rose-600'],
                    ];
                    $avatarColor = $avatarColors[$supplier->id % count($avatarColors)];
                @endphp
                <div class="flex items-center gap-2.5 min-w-0">
                    <span
                        class="shrink-0 w-7 h-7 rounded-lg {{ $avatarColor[0] }} {{ $avatarColor[1] }} flex items-center justify-center">
                        <x-mary-icon name="o-building-storefront" class="w-4 h-4" />
                    </span>
                    <span class="font-semibold text-gray-900 truncate"
                        title="{{ $supplier->company_name }}">{{ $supplier->company_name }}</span>
                </div>
            @endscope
            @scope('cell_npwp', $supplier)
                <span class="font-mono text-sm font-semibold text-gray-700">{{ $supplier->npwp }}</span>
            @endscope
            @scope('cell_address', $supplier)
                <span class="block truncate text-sm text-gray-600"
                    title="{{ $supplier->address }}">{{ $supplier->address }}</span>
            @endscope
            @scope('cell_legalitas', $supplier)
                @if ($supplier->hasCompleteLegalDocuments())
                    <x-mary-badge value="Lengkap" class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                @else
                    <x-mary-badge value="Belum Lengkap"
                        class="bg-orange-100 text-orange-700 border-none badge-sm font-semibold" />
                @endif
            @endscope
            @scope('cell_actions', $supplier)
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
                            wire:click="show({{ $supplier->id }})" />
                        @if (auth()->user()->isSuperAdmin())
                            <x-mary-menu-item title="Edit Data" icon="o-pencil-square"
                                link="{{ route('superadmin.suppliers.edit', $supplier->id) }}" />
                            <x-mary-menu-item title="Hapus (Soft Delete)" icon="o-trash"
                                class="cursor-pointer text-red-500"
                                wire:click="confirmDestroy({{ $supplier->id }}, '{{ addslashes($supplier->company_name) }}')" />
                        @endif
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($suppliers->total() > 0)
            <div>{{ $suppliers->links('pagination.table', ['label' => 'supplier']) }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="detailModal" title="Detail Supplier"
        subtitle="Informasi lengkap supplier, legalitas, dan riwayat proyek."
        class="backdrop-blur text-black" box-class="max-w-5xl" title-class="text-[#0046FF]">
        @if ($selectedSupplier)
            @php
                $businessPermit = $selectedSupplier->legalDocuments
                    ->firstWhere('document_type', \App\Enums\DocumentType::BUSINESS_PERMIT);
            @endphp

            <div class="space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-3 rounded-xl border border-gray-100 bg-white p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <x-mary-icon name="o-building-office-2" class="w-5 h-5 text-[#0046FF]" />
                            <h3 class="font-bold text-gray-900">Informasi Umum</h3>
                        </div>

                        <div class="flex items-center gap-4 mb-5">
                            <span
                                class="shrink-0 w-16 h-16 rounded-2xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                <x-mary-icon name="o-building-storefront" class="w-8 h-8" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-lg text-gray-900 leading-snug">{{ $selectedSupplier->company_name }}</p>
                                    <x-mary-badge value="Aktif"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                </div>
                            </div>
                        </div>

                            <div class="space-y-2.5 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">NPWP</span>
                                    <span class="font-mono font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->npwp }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Alamat</span>
                                    <span class="font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->address }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Telepon</span>
                                    <span class="font-mono font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->phone }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Email</span>
                                    <span class="font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->email ?? '-' }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Kontak Person</span>
                                    <span class="font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->pic_name }}</span>
                                </div>
                            </div>
                        </div>

                    <div class="lg:col-span-2 space-y-4">
                        <div class="rounded-xl border border-gray-100 bg-white p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <x-mary-icon name="o-document-check" class="w-5 h-5 text-[#0046FF]" />
                                <h3 class="font-bold text-gray-900">Legalitas</h3>
                            </div>
                            <div class="space-y-2.5 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">No. SIUP</span>
                                    <span class="font-mono font-medium text-gray-900 text-right break-words">{{ $businessPermit?->document_number ?? '-' }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Tanggal SIUP</span>
                                    <span class="font-medium text-gray-900 text-right break-words">{{ $businessPermit?->document_date?->format('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">No. NIB</span>
                                    <span class="font-mono font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->nib ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-white p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <x-mary-icon name="o-shield-check" class="w-5 h-5 text-[#0046FF]" />
                                <h3 class="font-bold text-gray-900">Status &amp; Verifikasi</h3>
                            </div>
                            <div class="space-y-2.5 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Status Supplier</span>
                                    <x-mary-badge value="Aktif"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Verifikasi Dokumen</span>
                                    @if ($supplierStats['is_complete'] ?? false)
                                        <x-mary-badge value="Terverifikasi"
                                            class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                    @else
                                        <x-mary-badge value="Belum Terverifikasi"
                                            class="bg-orange-100 text-orange-700 border-none badge-sm font-semibold" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    @php
                        $riwayatSorted = $selectedSupplier->procurementRequests->sortByDesc('created_at')->values();
                        $riwayatTotal = $riwayatSorted->count();
                    @endphp

                    <div class="rounded-xl border border-gray-100 bg-white p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <x-mary-icon name="o-clipboard-document-list" class="w-5 h-5 text-[#0046FF]" />
                                        <h3 class="font-bold text-gray-900">Riwayat Proyek</h3>
                                    </div>

                                    @if ($riwayatTotal > 0)
                                        <div class="overflow-x-auto">
                                            <table class="table table-sm w-full text-black">
                                                <thead>
                                                    <tr class="text-gray-500">
                                                        <th class="w-10">No.</th>
                                                        <th>Nama Proyek</th>
                                                        <th>Tanggal Mulai</th>
                                                        <th>Tanggal Selesai</th>
                                                        <th class="text-right">Nilai Proyek</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($riwayatSorted as $index => $request)
                                                    <tr>
                                                        <td class="text-gray-400">{{ $index + 1 }}</td>
                                                            <td class="text-xs font-medium">{{ $request->packageCategory?->name ?? '-' }}
                                                                &mdash; {{ $request->school?->name ?? '-' }}</td>
                                                            <td class="text-xs">{{ $request->start_date?->format('d M Y') ?? '-' }}</td>
                                                            <td class="text-xs">{{ $request->end_date?->format('d M Y') ?? '-' }}</td>
                                                            <td class="text-right font-mono text-xs">
                                                                {{ $request->grand_total !== null ? 'Rp ' . number_format($request->grand_total, 0, ',', '.') : '-' }}
                                                            </td>
                                                            <td>
                                                                <x-mary-badge :value="$request->status_label"
                                                                    class="border-none badge-sm font-semibold {{ $request->status_pill_class }}" />
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                                            Belum ada proyek pengadaan dengan supplier ini.
                                        </div>
                                    @endif
                                </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <x-mary-icon name="o-document-check" class="w-5 h-5 text-[#0046FF]" />
                            <h3 class="font-bold text-gray-900">Dokumen Legal</h3>
                        </div>

                            @if ($selectedSupplier->legalDocuments->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="table table-sm w-full text-black">
                                        <thead>
                                            <tr class="text-gray-500">
                                                <th>Tipe</th>
                                                <th>Nomor Dokumen</th>
                                                <th>Berlaku s/d</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($selectedSupplier->legalDocuments as $doc)
                                                <tr>
                                                    <td>{{ $doc->document_type->label() }}</td>
                                                    <td class="font-mono text-xs">{{ $doc->document_number }}</td>
                                                    <td class="text-xs {{ $doc->isExpired() ? 'text-red-500 font-semibold' : '' }}">
                                                        {{ $doc->valid_until ? \Carbon\Carbon::parse($doc->valid_until)->format('d M Y') : 'Seumur Hidup' }}
                                                        @if ($doc->isExpired())
                                                            <span class="text-red-500">(Habis)</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                                    Belum ada dokumen legal yang diunggah.
                                </div>
                            @endif
                        </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <x-mary-icon name="o-users" class="w-5 h-5 text-[#0046FF]" />
                            <h3 class="font-bold text-gray-900">Kontak &amp; Personnel</h3>
                        </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2.5 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Kontak Person (PIC)</span>
                                    <span class="font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->pic_name }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">Nama Direktur</span>
                                    <span class="font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->director_name }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-gray-500 font-medium shrink-0">NPWP Direktur</span>
                                    <span class="font-mono font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->director_npwp ?? '-' }}</span>
                                </div>
                                @if ($selectedSupplier->commissioner_name)
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="text-gray-500 font-medium shrink-0">Nama Komisaris</span>
                                        <span class="font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->commissioner_name }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="text-gray-500 font-medium shrink-0">NIK Komisaris</span>
                                        <span class="font-mono font-medium text-gray-900 text-right break-words">{{ $selectedSupplier->commissioner_nik ?? '-' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                </div>
            </div>
        @endif
    </x-mary-modal>

    <x-mary-modal wire:model="confirmingDestroy" title="Hapus Data Supplier" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-trash" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Hapus <span
                        class="text-[#FF8040]">"{{ $targetSupplierName }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Seluruh data entitas, riwayat pelacakan audit, serta dokumen pengadaan terafiliasi dengan supplier
                    ini akan dinonaktifkan (Soft Delete) dari sistem utama.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDestroy', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="destroy" class="bg-[#FF8040] text-white border-none"
                spinner="destroy" />
        </x-slot:actions>
    </x-mary-modal>
</div>
