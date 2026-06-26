<div>
    <x-mary-header :title="$isEdit ? 'Ubah Dokumen Legal' : 'Tambah Dokumen Legal Baru'"
        subtitle="{{ $isEdit ? 'Perbarui informasi dokumen legal yang sudah ada.' : 'Tambahkan dokumen legal (Akta, NIB, dll) untuk melengkapi profil supplier.' }}"
        separator>
        <x-slot:actions>
            @php
                $backRoute = auth()->user()->isOwner() ? 'owner.suppliers.index' : 'cv.suppliers.index';
            @endphp
            <x-mary-button label="Kembali" link="{{ route($backRoute) }}" icon="o-arrow-left"
                class="btn-ghost btn-sm text-black hover:text-[#0046FF]" wire:navigate />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-mary-card title="Detail Dokumen" shadow separator class="border-t-4 border-[#0046FF]">
                    <div
                        class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800 font-semibold">
                        Menambahkan dokumen untuk: {{ $supplier->company_name }}
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-select label="Tipe Dokumen" icon="o-document" :options="$this->documentTypes"
                            wire:model="document_type" placeholder="Pilih Tipe Dokumen" required />
                        <x-mary-input label="Nomor Dokumen" wire:model="document_number"
                            placeholder="Contoh: AHU-12345.AH.01.01" icon="o-hashtag" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input type="date" label="Tanggal Dokumen Dikeluarkan" wire:model="document_date"
                            icon="o-calendar" required />
                        <x-mary-input type="date" label="Berlaku Sampai (Opsional)" wire:model="valid_until"
                            icon="o-calendar-days" hint="Kosongkan jika berlaku seumur hidup" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="Penerbit / Instansi" wire:model="issuer"
                            placeholder="Contoh: Kemenkumham / OSS" icon="o-building-library" />
                        <x-mary-input label="Nama Notaris (Jika Ada)" wire:model="notary_name"
                            placeholder="Nama notaris pembuat akta" icon="o-user" />
                    </div>
                </x-mary-card>
            </div>
            <div class="lg:col-span-1">
                <x-mary-card title="Informasi Penting" shadow separator class="border-t-4 border-[#0046FF]">
                    <x-slot:subtitle>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Status Kelengkapan Legalitas
                        </p>
                    </x-slot:subtitle>
                    <ul class="text-xs text-gray-600 space-y-3 list-disc list-inside leading-relaxed mb-4">
                        <li>Supplier dianggap <span class="font-bold text-green-600">Lengkap & Valid</span> jika minimal
                            memiliki <b>Izin Usaha Aktif</b> dan <b>Akta Perusahaan</b>.</li>
                        <li>Pilih tipe <code>business_permit</code> untuk mendaftarkan NIB yang menjadi syarat
                            pengecekan izin aktif sistem.</li>
                        <li>Jika dokumen memiliki masa kedaluwarsa, wajib mengisi kolom <b>Berlaku Sampai</b> agar
                            sistem dapat melacak masa aktif izin.</li>
                    </ul>
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex gap-2">
                        <x-mary-icon name="o-shield-check" class="w-5 h-5 text-[#0046FF] shrink-0" />
                        <span>Dokumen yang diunggah akan masuk ke dalam audit log pengadaan supplier terkait.</span>
                    </div>
                </x-mary-card>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            @php
                $cancelRoute = auth()->user()->isOwner() ? 'owner.suppliers.index' : 'cv.suppliers.index';
            @endphp
            <x-mary-button label="Batal" link="{{ route($cancelRoute) }}" class="btn-ghost btn-sm text-black"
                wire:navigate />
            <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Dokumen' }}" type="submit" icon="o-check"
                class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" wire:loading.attr="disabled"
                spinner="save" />
        </div>
    </x-mary-form>
</div>
