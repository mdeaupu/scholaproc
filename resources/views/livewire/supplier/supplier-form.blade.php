<div>
    <x-mary-header :title="$isEdit ? 'Ubah Informasi Supplier' : 'Pendaftaran Supplier Baru'"
        subtitle="{{ $isEdit ? 'Perbarui informasi data supplier yang terdaftar.' : 'Daftarkan entitas supplier baru ke dalam sistem sebagai mitra pengadaan.' }}"
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
                <x-mary-card title="Profil Utama Perusahaan" shadow separator class="border-t-4 border-[#0046FF]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="Nama Perusahaan" wire:model="company_name"
                            placeholder="Contoh: CV. Berkah Abadi" icon="o-building-office" required />
                        <x-mary-input label="Nama PIC / Contact Person" wire:model="pic_name"
                            placeholder="Nama narahubung" icon="o-user-circle" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="Nama Direktur" wire:model="director_name"
                            placeholder="Nama lengkap sesuai akta" icon="o-user" required />
                        <x-mary-input label="NIK Direktur" wire:model="director_nik" placeholder="16 digit NIK KTP"
                            icon="o-identification" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="NPWP Perusahaan" wire:model="npwp" placeholder="Nomor NPWP resmi"
                            icon="o-document-text" required />
                        <x-mary-input label="NIB (Nomor Induk Berusaha)" wire:model="nib" placeholder="Nomor NIB resmi"
                            icon="o-document-text" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="No. Telepon / WhatsApp" wire:model="phone" placeholder="0812-xxxx-xxxx"
                            icon="o-phone" required />
                        <x-mary-input label="Email Perusahaan" wire:model="email" type="email"
                            placeholder="info@perusahaan.com" icon="o-envelope" required />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat Lengkap Perusahaan" wire:model="address"
                            placeholder="Jalan, RT/RW, Kecamatan, Kabupaten..." rows="3" required />
                    </div>
                </x-mary-card>
            </div>
            <div class="lg:col-span-1">
                <x-mary-card title="Petunjuk Pengisian" shadow separator class="border-t-4 border-[#0046FF]">
                    <x-slot:subtitle>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Panduan validasi data mitra pengadaan.
                        </p>
                    </x-slot:subtitle>
                    <ul class="text-xs text-gray-600 space-y-3 list-disc list-inside leading-relaxed mb-4">
                        <li>Kolom <span class="font-bold text-black">Nama Direktur</span> sangat krusial karena akan
                            otomatis dipetakan ke dalam sistem tanda tangan dokumen.</li>
                        <li>Pastikan nomor <span class="font-bold text-black">NPWP</span> dan <span
                                class="font-bold text-black">NIB</span> diisi dengan benar tanpa salah ketik.</li>
                        <li>Gunakan nomor handphone/WhatsApp aktif untuk PIC agar mempermudah notifikasi sistem.</li>
                    </ul>
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex gap-2">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4 text-[#0046FF] shrink-0 mt-0.5" />
                        <span>Sistem menggunakan validasi keunikan data (NPWP & NIB) untuk mencegah entri duplikat pada
                            <code>suppliers</code>.</span>
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
            <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Supplier' }}" type="submit"
                icon="o-check" class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                wire:loading.attr="disabled" spinner="save" />
        </div>
    </x-mary-form>
</div>
