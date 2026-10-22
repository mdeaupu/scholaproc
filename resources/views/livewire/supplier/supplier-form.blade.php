<div>
    <x-mary-form wire:submit="save">
        <a href="{{ auth()->user()->isSuperAdmin() ? route('superadmin.suppliers.index') : route('admin.suppliers.index') }}"
            wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline mb-2">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Kembali ke Daftar Supplier
        </a>
        <x-mary-header :title="$isEdit ? 'Edit Data Supplier' : 'Tambah Supplier'"
            :subtitle="$isEdit
                ? 'Perbarui informasi perusahaan, kontak, dan data legalitas supplier.'
                : 'Daftarkan supplier baru beserta data perusahaan dan jajaran direksinya.'">
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <x-mary-button label="Batal"
                        link="{{ auth()->user()->isSuperAdmin() ? route('superadmin.suppliers.index') : route('admin.suppliers.index') }}"
                        class="btn-sm bg-white text-gray-700 border border-gray-200 hover:bg-base-200" wire:navigate />
                    <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Supplier' }}" type="submit"
                        icon="o-check" class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                        wire:loading.attr="disabled" />
                </div>
            </x-slot:actions>
        </x-mary-header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-2">
            <div class="lg:col-span-2 space-y-6">
                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-building-office-2" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Perusahaan &amp; Kontak</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="Nama Perusahaan" wire:model="company_name"
                            placeholder="Contoh: CV Sumber Makmur" :required="true" />
                        <x-mary-input label="Nama PIC / Narahubung" wire:model="pic_name"
                            placeholder="Contoh: Andi Wijaya" icon="o-user" :required="true" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="No. Telepon / WA" wire:model="phone" placeholder="0812xxxxxxx"
                            icon="o-phone" :required="true" />
                        <x-mary-input label="Email Perusahaan" wire:model="email" type="email"
                            placeholder="info@perusahaan.co.id" icon="o-envelope" />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat Kantor" wire:model="address"
                            placeholder="Jalan, RT/RW, Kecamatan, Kota/Kabupaten, Provinsi..." rows="3"
                            :required="true" />
                    </div>
                </x-mary-card>

                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-document-text" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Legalitas Perusahaan</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="NPWP Perusahaan" wire:model="npwp"
                            placeholder="Contoh: 01.234.567.8-091.000" icon="o-identification" :required="true"
                            hint="Harus unik antar supplier" />
                        <x-mary-input label="NIB (Nomor Induk Berusaha)" wire:model="nib"
                            placeholder="Contoh: 0220304156227" icon="o-hashtag" :required="true"
                            hint="Harus unik antar supplier" />
                    </div>
                </x-mary-card>

                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-user" class="w-5 h-5" />
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
                </x-mary-card>

                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
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
                </x-mary-card>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
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
                        <li>NPWP dan NIB wajib unik — tidak boleh sama dengan supplier lain.</li>
                        <li>Dokumen legal (Akta, NIB, NPWP) ditambahkan terpisah melalui menu
                            <b>Tambah Dokumen Legal</b> di daftar supplier.</li>
                    </ul>
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex gap-2">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4 text-[#0046FF] shrink-0 mt-0.5" />
                        <span>Data supplier dipakai pada koordinasi pengadaan dan dokumen surat pesanan ke
                            supplier.</span>
                    </div>
                </x-mary-card>
            </div>
        </div>
    </x-mary-form>
</div>
