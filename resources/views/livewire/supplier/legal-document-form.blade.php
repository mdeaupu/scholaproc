<div>
    <x-mary-form wire:submit="save">
        <a href="{{ route('superadmin.suppliers.edit', $supplier->id) }}"
            wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline mb-2">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Kembali ke Edit Data Supplier
        </a>

        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $isEdit ? 'Ubah Dokumen Legal' : 'Tambah Dokumen Legal' }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isEdit
                        ? 'Perbarui informasi dokumen legal yang sudah ada.'
                        : 'Unggah dan lengkapi dokumen legal supplier untuk keperluan verifikasi.' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Dokumen' }}" type="submit"
                    icon="o-check" class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                    wire:loading.attr="disabled" spinner="save" />
            </div>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-5 mb-6">
            <div class="flex items-center gap-2.5 mb-5">
                <span class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                    <x-mary-icon name="o-building-office-2" class="w-5 h-5" />
                </span>
                <h3 class="font-bold text-[#0046FF]">Informasi Supplier</h3>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 flex items-start gap-5">
                    <span
                        class="shrink-0 w-20 h-20 rounded-2xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                        <x-mary-icon name="o-building-storefront" class="w-10 h-10" />
                    </span>
                    <div class="min-w-0">
                        <p class="font-bold text-lg text-gray-900 leading-snug">{{ $supplier->company_name }}</p>
                        <div class="mt-2 space-y-1 text-sm">
                            <div class="flex items-start justify-between gap-6">
                                <span class="text-gray-500 font-medium shrink-0">NPWP</span>
                                <span class="font-mono font-medium text-gray-900">{{ $supplier->npwp }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-6">
                                <span class="text-gray-500 font-medium shrink-0">Email</span>
                                <span class="font-medium text-gray-900">{{ $supplier->email ?? '-' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-6">
                                <span class="text-gray-500 font-medium shrink-0">Telepon</span>
                                <span class="font-mono font-medium text-gray-900">{{ $supplier->phone }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-6">
                                <span class="text-gray-500 font-medium shrink-0">Alamat</span>
                                <span class="font-medium text-gray-900 text-right break-words">{{ $supplier->address }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-information-circle" class="w-5 h-5 text-[#0046FF]" />
                        <p class="font-semibold text-sm text-gray-900">Catatan</p>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Pastikan dokumen yang diunggah masih berlaku dan sesuai dengan data supplier. Dokumen akan
                        diverifikasi oleh tim administrasi sistem.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-gray-100 bg-white p-5">
                    <h3 class="font-bold text-[#0046FF] mb-5">Detail Dokumen Legal</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-select label="Jenis Dokumen" :options="$this->documentTypes"
                            wire:model="document_type" placeholder="Pilih jenis dokumen" :required="true" />
                        <x-mary-input label="Nomor Dokumen" wire:model="document_number"
                            placeholder="Masukkan nomor dokumen" :required="true" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input type="date" label="Tanggal Terbit" wire:model="document_date"
                            icon="o-calendar" :required="true" />
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <x-mary-input type="date" label="Tanggal Kedaluwarsa" wire:model="valid_until"
                                    icon="o-calendar-days" :disabled="$noExpiry"
                                    hint="Kosongkan jika berlaku seumur hidup" />
                            </div>
                            <div class="pb-7">
                                <x-mary-checkbox label="Tidak ada masa berlaku" wire:model.live="noExpiry" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="Nama Penerbit" wire:model="issuer"
                            placeholder="Masukkan nama penerbit dokumen (contoh: Kementerian Hukum dan HAM)"
                            icon="o-building-library" />
                        <x-mary-input label="Nama Notaris (Opsional)" wire:model="notary_name"
                            placeholder="Nama notaris pembuat akta" icon="o-user"
                            hint="Khusus dokumen akta perusahaan" />
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-xl border border-gray-100 bg-white p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-shield-check" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Dokumen yang Diperlukan</h3>
                    </div>

                    <div class="space-y-3">
                        @foreach ($requiredDocuments as $required)
                            <div class="flex items-start gap-3">
                                <span
                                    class="shrink-0 w-9 h-9 rounded-lg bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                    <x-mary-icon name="o-document-text" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $required['label'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $required['description'] }}</p>
                                </div>
                                @if ($required['exists'])
                                    <x-mary-badge value="Tersedia"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold shrink-0" />
                                @else
                                    <x-mary-badge value="Belum Ada"
                                        class="bg-gray-100 text-gray-500 border-none badge-sm font-semibold shrink-0" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                <x-mary-icon name="o-clock" class="w-5 h-5" />
                            </span>
                            <h3 class="font-bold text-[#0046FF]">Riwayat Dokumen</h3>
                        </div>
                        @if ($documentHistory->count() > 5)
                            <a href="{{ route('superadmin.suppliers.edit', $supplier->id) }}"
                                wire:navigate class="text-xs font-medium text-[#0046FF] hover:underline">Lihat
                                Semua</a>
                        @endif
                    </div>

                    @if ($documentHistory->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="table table-sm w-full text-black">
                                <thead>
                                    <tr class="text-gray-500">
                                        <th>Jenis Dokumen</th>
                                        <th>Tanggal Unggah</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documentHistory->take(5) as $history)
                                        <tr>
                                            <td class="text-xs font-medium">{{ $history->document_type->label() }}</td>
                                            <td class="text-xs">{{ $history->created_at?->format('d M Y') ?? '-' }}</td>
                                            <td>
                                                @if ($history->isExpired())
                                                    <x-mary-badge value="Kedaluwarsa"
                                                        class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                                                @else
                                                    <x-mary-badge value="Berlaku"
                                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                            Belum ada dokumen legal yang diunggah.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-mary-form>
</div>
