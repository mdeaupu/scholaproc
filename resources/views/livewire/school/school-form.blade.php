<div>
    <x-mary-header :title="$isEdit ? 'Ubah Informasi Sekolah' : 'Pendaftaran Instansi Baru'"
        subtitle="Kelola data profil utama sekolah dan konfigurasi kop surat untuk dokumen pengadaan." separator>
        <x-slot:actions>
            <x-mary-button label="Kembali" link="{{ route('schools.index') }}" icon="o-arrow-left"
                class="btn-ghost btn-sm text-black hover:text-[#0046FF]" wire:navigate />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-mary-card title="Profil Utama Instansi" shadow separator class="border-t-4 border-[#0046FF]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="NPSN" wire:model="npsn" placeholder="Contoh: 10203040"
                            icon="o-identification" hint="Nomor Pokok Sekolah Nasional" :disabled="$isEdit" />
                        <x-mary-input label="Nama Sekolah" wire:model="name" placeholder="Contoh: SMAN 1 Cianjur"
                            icon="o-academic-cap" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="No. Telepon" wire:model="phone_number" placeholder="0263-xxxxx"
                            icon="o-phone" />
                        <x-mary-input label="Email Instansi" wire:model="email" type="email"
                            placeholder="info@sekolah.sch.id" icon="o-envelope" />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat Lengkap" wire:model="address"
                            placeholder="Jalan, RT/RW, Kecamatan, Kabupaten..." rows="3" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="Kode Pos" wire:model="postal_code" placeholder="432xx" icon="o-inbox" />
                        <x-mary-select label="Status Kemitraan" wire:model="status" icon="o-shield-check"
                            :options="[
                                ['id' => 'active', 'name' => 'Aktif / Bekerjasama'],
                                ['id' => 'suspended', 'name' => 'Ditangguhkan'],
                            ]" />
                    </div>
                </x-mary-card>
            </div>
            <div class="lg:col-span-1">
                <x-mary-card title="Konfigurasi Kop Surat" shadow separator class="border-t-4 border-[#0046FF]">
                    <x-slot:subtitle>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Digunakan otomatis saat generate dokumen PDF (Cover, PO, BAST).
                        </p>
                    </x-slot:subtitle>
                    <div class="space-y-4">
                        <x-mary-input label="Baris 1 — Pusat / Kementerian" wire:model="kop_pusat"
                            placeholder="Pemerintah Provinsi Jawa Barat" />
                        <x-mary-input label="Baris 2 — Dinas / Instansi" wire:model="kop_provinsi"
                            placeholder="Dinas Pendidikan Wilayah VI" />
                        <x-mary-input label="Baris 3 — Sub Wilayah" wire:model="kop_sub_wilayah"
                            placeholder="Cabang Dinas Pendidikan Cianjur" hint="Kosongkan jika tidak ada" />
                    </div>
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex gap-2">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4 text-[#0046FF] shrink-0 mt-0.5" />
                        <span>Data kop surat tersimpan terpisah di tabel <code>school_settings</code> sesuai standar
                            3NF.</span>
                    </div>
                </x-mary-card>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <x-mary-button label="Batal" link="{{ route('schools.index') }}" class="btn-ghost btn-sm text-black"
                wire:navigate />
            <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Sekolah' }}" type="submit"
                icon="o-check" class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                wire:loading.attr="disabled" />
        </div>
    </x-mary-form>
</div>
