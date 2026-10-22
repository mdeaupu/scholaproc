<div>
    <x-mary-form wire:submit="save">
        <a href="{{ auth()->user()->isSuperAdmin() ? route('superadmin.suppliers.index') : route('admin.suppliers.index') }}"
            wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline mb-2">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Kembali ke Daftar Supplier
        </a>

        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $isEdit ? 'Edit Data Supplier' : 'Tambah Supplier' }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isEdit
                        ? 'Perbarui informasi supplier sesuai dengan data terbaru.'
                        : 'Daftarkan supplier baru beserta data perusahaan dan jajaran direksinya.' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Supplier' }}" type="submit"
                    icon="o-check" class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                    wire:loading.attr="disabled" spinner="save" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-mary-card shadow class="border border-gray-100">
                    <x-mary-tabs wire:model.live="activeTab"
                        :label-div-class="'border-b border-gray-200 flex overflow-x-auto'"
                        :label-class="'pb-2.5 px-1 mr-6 text-sm font-medium text-gray-500 whitespace-nowrap cursor-pointer'"
                        :active-class="'border-b-2 border-b-[#0046FF] text-gray-900 font-semibold'">

                        <x-mary-tab name="umum" label="Informasi Umum">
                            <div class="rounded-xl border border-gray-100 bg-white p-5">
                                <div class="flex items-center gap-2.5 mb-5">
                                    <span
                                        class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                        <x-mary-icon name="o-building-office-2" class="w-5 h-5" />
                                    </span>
                                    <h3 class="font-bold text-[#0046FF]">Informasi Dasar Supplier</h3>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <x-mary-input label="Nama Supplier" wire:model="company_name"
                                        placeholder="Contoh: CV Sumber Makmur" :required="true" />
                                    <x-mary-input label="NPWP Perusahaan" wire:model="npwp"
                                        placeholder="Contoh: 01.234.567.8-091.000" icon="o-identification"
                                        :required="true" hint="Harus unik antar supplier" />
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                    <x-mary-input label="Email Perusahaan" wire:model="email" type="email"
                                        placeholder="info@perusahaan.co.id" icon="o-envelope" />
                                    <x-mary-input label="Telepon Perusahaan" wire:model="phone"
                                        placeholder="0812xxxxxxx" icon="o-phone" :required="true" />
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                    <x-mary-input label="NIB (Nomor Induk Berusaha)" wire:model="nib"
                                        placeholder="Contoh: 0220304156227" icon="o-hashtag" :required="true"
                                        hint="Harus unik antar supplier" />
                                </div>
                                <div class="mt-4" x-data="{ length: {{ strlen($address) }} }">
                                    <x-mary-textarea label="Alamat Lengkap" wire:model="address" maxlength="255"
                                        placeholder="Jalan, RT/RW, Kecamatan, Kota/Kabupaten, Provinsi..." rows="3"
                                        :required="true" x-on:input="length = $event.target.value.length" />
                                    <div class="text-right text-xs text-gray-400 mt-1">
                                        <span x-text="length">{{ strlen($address) }}</span>/255
                                    </div>
                                </div>
                            </div>
                        </x-mary-tab>

                        <x-mary-tab name="personel" label="Kontak &amp; Personel">
                            <div class="space-y-5">
                                <div class="rounded-xl border border-gray-100 bg-white p-5">
                                    <div class="flex items-center gap-2.5 mb-5">
                                        <span
                                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                            <x-mary-icon name="o-user" class="w-5 h-5" />
                                        </span>
                                        <h3 class="font-bold text-[#0046FF]">Kontak Kepala Supplier</h3>
                                    </div>
                                    <x-mary-input label="Nama PIC / Narahubung" wire:model="pic_name"
                                        placeholder="Contoh: Andi Wijaya" icon="o-user" :required="true" />
                                </div>

                                <div class="rounded-xl border border-gray-100 bg-white p-5">
                                    <div class="flex items-center gap-2.5 mb-5">
                                        <span
                                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                            <x-mary-icon name="o-identification" class="w-5 h-5" />
                                        </span>
                                        <h3 class="font-bold text-[#0046FF]">Data Direktur</h3>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <x-mary-input label="Nama Direktur" wire:model="director_name"
                                            placeholder="Contoh: Budi Santoso" :required="true" />
                                        <x-mary-input label="NIK Direktur" wire:model="director_nik"
                                            placeholder="16 digit NIK" icon="o-identification" :required="true"
                                            hint="Tepat 16 digit angka" />
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                        <x-mary-input label="NPWP Direktur" wire:model="director_npwp"
                                            placeholder="NPWP pribadi direktur" icon="o-document-text" />
                                        <x-mary-input label="Telepon Direktur" wire:model="director_phone"
                                            placeholder="0812xxxxxxx" icon="o-phone" />
                                    </div>
                                    <div class="mt-4">
                                        <x-mary-textarea label="Alamat Direktur" wire:model="director_address"
                                            placeholder="Alamat domisili direktur" rows="2" />
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-100 bg-white p-5">
                                    <div class="flex items-center gap-2.5 mb-5">
                                        <span
                                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                            <x-mary-icon name="o-users" class="w-5 h-5" />
                                        </span>
                                        <h3 class="font-bold text-[#0046FF]">Data Komisaris <span
                                                class="text-xs font-normal text-gray-400">(Opsional)</span></h3>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <x-mary-input label="Nama Komisaris" wire:model="commissioner_name"
                                            placeholder="Kosongkan jika tidak ada" />
                                        <x-mary-input label="NIK Komisaris" wire:model="commissioner_nik"
                                            placeholder="16 digit NIK" icon="o-identification"
                                            hint="Kosongkan jika tidak ada" />
                                    </div>
                                    <div class="mt-4">
                                        <x-mary-textarea label="Alamat Komisaris" wire:model="commissioner_address"
                                            placeholder="Alamat domisili komisaris" rows="2" />
                                    </div>
                                </div>
                            </div>
                        </x-mary-tab>

                        <x-mary-tab name="riwayat" label="Riwayat Proyek">
                            <div class="rounded-xl border border-gray-100 bg-white p-5">
                                <div class="flex items-center gap-2.5 mb-5">
                                    <span
                                        class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                        <x-mary-icon name="o-clipboard-document-list" class="w-5 h-5" />
                                    </span>
                                    <h3 class="font-bold text-[#0046FF]">Riwayat Proyek</h3>
                                </div>

                                @if ($isEdit && $projects->isNotEmpty())
                                    @php($visibleProjects = $projects->take(10))
                                    <div class="overflow-x-auto">
                                        <table class="table table-sm w-full text-black">
                                            <thead>
                                                <tr class="text-gray-500">
                                                    <th class="w-10">No.</th>
                                                    <th>Nama Proyek</th>
                                                    <th>Mulai</th>
                                                    <th>Selesai</th>
                                                    <th class="text-right">Nilai Proyek</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($visibleProjects as $index => $project)
                                                    <tr>
                                                        <td class="text-gray-400">{{ $index + 1 }}</td>
                                                        <td class="text-xs font-medium">
                                                            {{ $project->packageCategory?->name ?? '-' }}
                                                            &mdash; {{ $project->school?->name ?? '-' }}
                                                        </td>
                                                        <td class="text-xs">{{ $project->start_date?->format('d M Y') ?? '-' }}</td>
                                                        <td class="text-xs">{{ $project->end_date?->format('d M Y') ?? '-' }}</td>
                                                        <td class="text-right font-mono text-xs">
                                                            {{ $project->grand_total !== null ? 'Rp ' . number_format($project->grand_total, 0, ',', '.') : '-' }}
                                                        </td>
                                                        <td>
                                                            <x-mary-badge :value="$project->status_label"
                                                                class="border-none badge-sm font-semibold {{ $project->status_pill_class }}" />
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($projects->count() > 10)
                                        <p class="text-xs text-gray-500 mt-3">
                                            Menampilkan 10 proyek terbaru dari {{ $projects->count() }} proyek.
                                        </p>
                                    @endif
                                @elseif ($isEdit)
                                    <div class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                                        Belum ada proyek pengadaan dengan supplier ini.
                                    </div>
                                @else
                                    <div class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                                        Riwayat proyek tersedia setelah supplier disimpan.
                                    </div>
                                @endif
                            </div>
                        </x-mary-tab>
                    </x-mary-tabs>
                </x-mary-card>
            </div>

            <div class="lg:col-span-1 space-y-6">
                @if ($isEdit && $supplier)
                    <x-mary-card shadow class="border border-gray-100">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span
                                class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                <x-mary-icon name="o-shield-check" class="w-5 h-5" />
                            </span>
                            <h3 class="font-bold text-[#0046FF]">Dokumen Legalitas</h3>
                        </div>

                        @if ($legalDocuments->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($legalDocuments as $document)
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="shrink-0 w-9 h-9 rounded-lg bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                            <x-mary-icon name="o-document-text" class="w-5 h-5" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $document->document_type->label() }}
                                            </p>
                                            <p class="text-xs text-gray-500 font-mono truncate">
                                                {{ $document->document_number ?? '-' }}
                                            </p>
                                            <p
                                                class="text-xs mt-0.5 {{ $document->isExpired() ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                                                {{ $document->valid_until ? 'Berlaku s/d ' . $document->valid_until->format('d M Y') : 'Seumur Hidup' }}
                                                @if ($document->isExpired())
                                                    (Habis)
                                                @endif
                                            </p>
                                        </div>
                                        <x-mary-button icon="o-pencil"
                                            link="{{ auth()->user()->isSuperAdmin() ? route('superadmin.suppliers.legal-documents.edit', [$supplier->id, $document->id]) : route('admin.suppliers.legal-documents.edit', [$supplier->id, $document->id]) }}"
                                            class="btn-xs btn-circle btn-ghost text-[#0046FF]" tooltip="Edit Dokumen"
                                            wire:navigate />
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-3 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                                Belum ada dokumen legal yang diunggah.
                            </div>
                        @endif

                        <div class="mt-4">
                            <x-mary-button label="Tambah Dokumen Legal"
                                link="{{ auth()->user()->isSuperAdmin() ? route('superadmin.suppliers.legal-documents.create', $supplier->id) : route('admin.suppliers.legal-documents.create', $supplier->id) }}"
                                icon="o-document-plus" wire:navigate
                                class="btn-sm w-full bg-white text-[#0046FF] border border-[#0046FF]/40 hover:bg-[#0046FF]/10" />
                        </div>
                    </x-mary-card>

                    <x-mary-card shadow class="border border-gray-100">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span
                                class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                <x-mary-icon name="o-chart-bar" class="w-5 h-5" />
                            </span>
                            <h3 class="font-bold text-[#0046FF]">Ringkasan Supplier</h3>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Status Legalitas</span>
                                @if ($summary['is_complete'])
                                    <x-mary-badge value="Lengkap & Valid"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                @else
                                    <x-mary-badge value="Belum Lengkap"
                                        class="bg-orange-100 text-orange-700 border-none badge-sm font-semibold" />
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Total Proyek</span>
                                <span class="font-semibold text-gray-900">{{ $summary['total_projects'] }} proyek</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Total Nilai Proyek</span>
                                <span class="font-mono font-semibold text-gray-900">
                                    Rp {{ number_format($summary['total_project_value'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Verifikasi Dokumen</span>
                                @if ($summary['is_complete'])
                                    <x-mary-badge value="Terverifikasi"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                @else
                                    <x-mary-badge value="Belum Terverifikasi"
                                        class="bg-orange-100 text-orange-700 border-none badge-sm font-semibold" />
                                @endif
                            </div>
                        </div>
                    </x-mary-card>
                @else
                    <x-mary-card shadow class="border border-gray-100">
                        <div class="flex items-center gap-2.5 mb-1">
                            <span
                                class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                <x-mary-icon name="o-shield-check" class="w-5 h-5" />
                            </span>
                            <h3 class="font-bold text-[#0046FF]">Informasi Penting</h3>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Ketentuan kelengkapan legalitas supplier.</p>
                        <ul class="text-xs text-gray-600 space-y-3 list-disc list-inside leading-relaxed">
                            <li>Supplier dianggap <span class="font-bold text-green-600">Lengkap &amp; Valid</span> jika
                                minimal memiliki <b>Izin Usaha (NIB/SIUP)</b> dan <b>Akta Perusahaan</b>.</li>
                            <li>NPWP dan NIB wajib unik â€” tidak boleh sama dengan supplier lain.</li>
                            <li>Dokumen legal (Akta, NIB, NPWP) ditambahkan setelah data supplier disimpan.</li>
                        </ul>
                        <div
                            class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex gap-2">
                            <x-mary-icon name="o-information-circle"
                                class="w-4 h-4 text-[#0046FF] shrink-0 mt-0.5" />
                            <span>Data supplier dipakai pada koordinasi pengadaan dan dokumen surat pesanan ke
                                supplier.</span>
                        </div>
                    </x-mary-card>
                @endif
            </div>
        </div>
    </x-mary-form>
</div>
