<div>
    <x-mary-header title="Manajemen Supplier"
        subtitle="Daftar penyedia barang dan jasa terdaftar sebagai mitra pengadaan." separator>
        <x-slot:actions>
            <x-mary-button label="Tambah Supplier"
                link="{{ auth()->user()->isOwner() ? route('owner.suppliers.create') : route('cv.suppliers.create') }}"
                icon="o-plus" class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" wire:navigate />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama perusahaan atau NPWP..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-64 text-black" />
        </div>
    </x-mary-card>
    <x-mary-card shadow class="border-t-4 border-[#0046FF]">
        <x-mary-table :headers="$headers" :rows="$suppliers" with-pagination>
            @scope('cell_company_name', $supplier)
                <div class="font-semibold text-black">{{ $supplier->company_name }}</div>
                <div class="text-xs text-gray-500">NIB: {{ $supplier->nib ?? '-' }}</div>
            @endscope
            @scope('cell_npwp', $supplier)
                <span class="font-mono text-sm font-bold text-black">{{ $supplier->npwp }}</span>
            @endscope
            @scope('actions', $supplier)
                <div class="flex items-center gap-1">
                    <x-mary-button icon="o-document-plus"
                        link="{{ auth()->user()->isOwner() ? route('owner.suppliers.legal-documents.create', $supplier->id) : route('cv.suppliers.legal.create', $supplier->id) }}"
                        class="btn-sm btn-circle btn-ghost text-emerald-600" tooltip="Tambah Dokumen Legal" wire:navigate />
                    <x-mary-button icon="o-eye" wire:click="show({{ $supplier->id }})"
                        class="btn-sm btn-circle btn-ghost text-[#0046FF]" tooltip="Detail" />
                    <x-mary-button icon="o-pencil-square"
                        link="{{ auth()->user()->isOwner() ? route('owner.suppliers.edit', $supplier->id) : route('cv.suppliers.edit', $supplier->id) }}"
                        class="btn-sm btn-circle btn-ghost text-black" tooltip="Edit" wire:navigate />
                    <x-mary-button icon="o-trash"
                        wire:click="confirmDestroy({{ $supplier->id }}, '{{ addslashes($supplier->company_name) }}')"
                        class="btn-sm btn-circle btn-ghost text-[#FF8040]" tooltip="Hapus" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
    <x-mary-modal wire:model="detailModal" title="Detail Informasi Supplier" class="backdrop-blur"
        title-class="text-[#0046FF]">
        @if ($selectedSupplier)
            <div class="space-y-4 text-black">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Nama Perusahaan</p>
                        <p class="font-bold text-base mt-0.5">{{ $selectedSupplier->company_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Nama Direktur</p>
                        <p class="font-semibold text-base mt-0.5">{{ $selectedSupplier->director_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Narahubung / PIC</p>
                        <p class="font-medium mt-0.5">{{ $selectedSupplier->pic_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">No. Telepon / WA</p>
                        <p class="font-mono mt-0.5">{{ $selectedSupplier->phone }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">NIB</p>
                        <p class="font-mono mt-0.5">{{ $selectedSupplier->nib ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Email Perusahaan</p>
                        <p class="mt-0.5">{{ $selectedSupplier->email ?? '-' }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Alamat Kantor</p>
                    <p class="text-sm mt-1 leading-relaxed">{{ $selectedSupplier->address }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                    <div class="p-3 bg-gray-50 rounded-lg self-start">
                        <span class="text-xs text-gray-500 block">Total Proyek Kerja</span>
                        <span class="text-lg font-bold text-black">{{ $supplierStats['total_projects'] ?? 0 }}</span>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-500 block">Status Dokumen Legalitas</span>
                        <span
                            class="text-sm font-bold block mt-1 {{ $supplierStats['is_complete'] ?? false ? 'text-green-600' : 'text-[#FF8040]' }}">
                            {{ $supplierStats['is_complete'] ?? false ? 'Lengkap & Valid' : 'Belum Lengkap' }}
                        </span>
                        @php
                            $hasPermit = $selectedSupplier->legalDocuments->contains->isBusinessPermit();
                            $hasDeed = $selectedSupplier->legalDocuments->contains->isDeed();
                        @endphp
                        <div class="mt-3 pt-2 border-t border-gray-200/60 text-[11px] space-y-1.5">
                            <p class="text-gray-400 font-semibold uppercase tracking-wider">Persyaratan Dokumen:</p>
                            <div
                                class="flex items-center gap-1.5 {{ $hasPermit ? 'text-green-600 font-medium' : 'text-red-500' }}">
                                <x-mary-icon name="{{ $hasPermit ? 'o-check-circle' : 'o-x-circle' }}"
                                    class="w-3.5 h-3.5 shrink-0" />
                                <span>Izin Usaha (NIB/SIUP)</span>
                            </div>
                            <div
                                class="flex items-center gap-1.5 {{ $hasDeed ? 'text-green-600 font-medium' : 'text-red-500' }}">
                                <x-mary-icon name="{{ $hasDeed ? 'o-check-circle' : 'o-x-circle' }}"
                                    class="w-3.5 h-3.5 shrink-0" />
                                <span>Akta Perusahaan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($selectedSupplier && $selectedSupplier->legalDocuments->isNotEmpty())
            <div class="overflow-x-auto mt-4 border-t pt-4">
                <table class="table table-sm w-full text-black">
                    <thead>
                        <tr class="text-gray-500">
                            <th>Tipe</th>
                            <th>Nomor Dokumen</th>
                            <th>Tanggal Berlaku</th>
                            <th class="text-right">Aksi</th>
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
                                <td class="text-right">
                                    <x-mary-button icon="o-pencil"
                                        link="{{ auth()->user()->isOwner() ? route('owner.suppliers.legal-documents.edit', [$selectedSupplier->id, $doc->id]) : route('cv.suppliers.legal.edit', [$selectedSupplier->id, $doc->id]) }}"
                                        class="btn-xs btn-circle btn-ghost text-[#0046FF]" tooltip="Edit Dokumen"
                                        wire:navigate />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-3 mt-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500 border-t pt-4">
                Belum ada dokumen legal yang diunggah.
            </div>
        @endif
        <x-slot:actions>
            <x-mary-button label="Tutup" wire:click="$set('detailModal', false)" class="btn-ghost text-black" />
        </x-slot:actions>
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
            <x-mary-button label="Batal" wire:click="$set('confirmingDestroy', false)" class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="destroy" class="bg-[#FF8040] text-white border-none"
                spinner="destroy" />
        </x-slot:actions>
    </x-mary-modal>
</div>
