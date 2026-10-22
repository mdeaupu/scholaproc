<div>
    <x-mary-form wire:submit="save">
        <a href="{{ route('schools.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline mb-2">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Kembali ke Daftar Sekolah
        </a>
        <x-mary-header :title="$isEdit ? 'Edit Data Sekolah' : 'Tambah Sekolah'"
            :subtitle="$isEdit
                ? 'Perbarui informasi sekolah sesuai dengan data terbaru.'
                : 'Daftarkan sekolah baru beserta konfigurasi kop surat dan akun admin.'">
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <x-mary-button label="Batal" link="{{ route('schools.index') }}"
                        class="btn-sm bg-white text-gray-700 border border-gray-200 hover:bg-base-200" wire:navigate />
                    <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Sekolah' }}" type="submit"
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
                            <x-mary-icon name="o-building-library" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Informasi Dasar Sekolah</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="Nama Sekolah" wire:model="name" placeholder="Contoh: SMAN 1 Cianjur"
                            :required="true" />
                        <x-mary-input label="NPSN" wire:model="npsn" placeholder="Contoh: 10203040"
                            :required="true" :disabled="$isEdit" hint="8 digit nomor pokok sekolah nasional" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-select label="Status Sekolah" wire:model="status" :required="true" :options="[
                            ['id' => 'active', 'name' => 'Aktif'],
                            ['id' => 'suspended', 'name' => 'Ditangguhkan'],
                        ]" />
                        <x-mary-input label="Email Sekolah" wire:model="email" type="email"
                            placeholder="info@sekolah.sch.id" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-mary-input label="Telepon Sekolah" wire:model="phone_number"
                            placeholder="(0263) xxxxxxx" :required="true" />
                        <x-mary-input label="Kode Pos" wire:model="postal_code" placeholder="432xx" />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat Sekolah" wire:model="address"
                            placeholder="Jalan, RT/RW, Kecamatan, Kota/Kabupaten..." rows="3" :required="true" />
                    </div>
                </x-mary-card>

                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-user" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Data Kepala Sekolah</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="Nama Kepala Sekolah" wire:model="principal_name"
                            placeholder="Contoh: Dr. Budi Santoso, M.Pd" icon="o-user"
                            hint="Untuk blok tanda tangan dokumen resmi" />
                        <x-mary-input label="NIP" wire:model="principal_nip"
                            placeholder="Contoh: 196807201998031002" icon="o-identification"
                            hint="Kosongkan jika tidak ada" />
                    </div>
                </x-mary-card>

                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-users" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Akun Admin Sekolah</h3>
                    </div>
                    @if ($isEdit && $school?->account)
                        <div
                            class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm mb-4 pb-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">Username</span>
                                <span class="font-mono font-bold text-gray-900">{{ $school->account->username }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">Status</span>
                                @if ($school->account->status === 'active')
                                    <x-mary-badge value="Aktif"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                @else
                                    <x-mary-badge value="Nonaktif"
                                        class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                                @endif
                            </div>
                        </div>
                    @endif
                    <x-mary-input label="Password Admin" wire:model="password" type="password" icon="o-key"
                        :required="!$isEdit"
                        :hint="$isEdit
                            ? 'Kosongkan jika tidak ingin mengubah password. Username mengikuti NPSN.'
                            : 'Minimal 8 karakter. Username admin otomatis menggunakan NPSN.'" />
                </x-mary-card>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-1">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-document-text" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Kop Surat Dokumen</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Digunakan otomatis pada dokumen PDF (Cover, PO, BAST).</p>
                    <div
                        class="border-2 border-dashed border-gray-300 p-3 bg-white text-black font-serif text-center rounded-xl mb-4">
                        <p class="text-[10px] font-bold leading-tight">{{ $kop_pusat ?: 'Baris 1 — Pusat' }}</p>
                        <p class="text-[10px] font-bold leading-tight">{{ $kop_provinsi ?: 'Baris 2 — Dinas' }}</p>
                        @if ($kop_sub_wilayah)
                            <p class="text-[10px] font-bold leading-tight">{{ $kop_sub_wilayah }}</p>
                        @endif
                        <p class="text-sm font-black tracking-wide my-1">{{ $name ?: 'NAMA SEKOLAH' }}</p>
                        <div class="border-t border-black pt-1 text-[8px] font-sans leading-snug">
                            {{ $address ?: 'Alamat sekolah' }}<br>
                            {{ $phone_number }}{{ $email ? ' | ' . $email : '' }}
                        </div>
                        <div class="border-b-4 border-double border-black mt-1"></div>
                    </div>
                    <div class="space-y-4">
                        <x-mary-input label="Baris 1 — Pusat / Kementerian" wire:model="kop_pusat"
                            placeholder="Pemerintah Provinsi Jawa Barat" :required="true" />
                        <x-mary-input label="Baris 2 — Dinas / Instansi" wire:model="kop_provinsi"
                            placeholder="Dinas Pendidikan" :required="true" />
                        <x-mary-input label="Baris 3 — Sub Wilayah" wire:model="kop_sub_wilayah"
                            placeholder="Cabang Dinas ..." hint="Kosongkan jika tidak ada" />
                    </div>
                </x-mary-card>

                <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                    <div class="flex items-center gap-2.5 mb-1">
                        <span
                            class="w-9 h-9 rounded-xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                            <x-mary-icon name="o-cog-6-tooth" class="w-5 h-5" />
                        </span>
                        <h3 class="font-bold text-[#0046FF]">Konfigurasi Sekolah</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Batasi sumber dana yang dapat dipakai sekolah saat mengajukan
                        pengadaan.</p>
                    <x-mary-select wire:model="funding_source_ids" :options="$fundingSources->toArray()"
                        option-value="id" option-label="name" placeholder="Pilih sumber dana..." multiple
                        label="Sumber Dana Boleh" icon="o-banknotes" />
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex gap-2">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4 text-[#0046FF] shrink-0 mt-0.5" />
                        <span>Kosongkan semua pilihan jika sekolah boleh memakai <strong>semua</strong> sumber dana
                            aktif.</span>
                    </div>
                </x-mary-card>
            </div>
        </div>
    </x-mary-form>
</div>
