<div>
    <x-mary-form wire:submit="save">
        <div class="mb-6">
            <a href="{{ route('admins.index') }}" wire:navigate
                class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline">
                <x-mary-icon name="o-chevron-left" class="w-4 h-4" />
                Kembali Ke Daftar Admin
            </a>

            <div class="flex flex-wrap items-start justify-between gap-4 mt-2">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tambah Admin Baru</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Daftarkan akun admin CV baru beserta hak aksesnya. Pastikan data yang diisi sudah benar
                        sebelum menyimpan.
                    </p>
                </div>
                <x-mary-button label="Simpan Data" type="submit" icon="o-check" spinner="save"
                    class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <x-mary-card shadow class="border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-5">Informasi Akun</h2>

                    <div class="space-y-4">
                        <x-mary-file label="Foto Profil" wire:model="photo" accept="image/png,image/jpeg"
                            hint="Format JPG, PNG. Maks. 2MB" />

                        <x-mary-input label="Nama Lengkap" wire:model="name" :required="true"
                            placeholder="Contoh: Budi Santoso" icon="o-user" />

                        <x-mary-input label="Email" wire:model="email" type="email"
                            placeholder="Contoh: budi@cvmsa.co.id" icon="o-envelope" />

                        <x-mary-input label="Username" wire:model="username" :required="true"
                            placeholder="Contoh: budi_cv" icon="o-identification" />

                        <x-mary-input label="Password" wire:model="password" type="password" :required="true"
                            placeholder="Minimal 8 karakter" hint="Minimal 8 karakter." icon="o-key" />

                        <x-mary-textarea label="Catatan" wire:model="notes" rows="4"
                            placeholder="Contoh: Penambahan admin untuk modul pengadaan..."
                            hint="Catatan ini hanya dapat dilihat oleh Superadmin." />
                    </div>
                </x-mary-card>
            </div>

            <div>
                <x-mary-card shadow class="border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-4">Informasi Hak Akses</h2>

                    <x-mary-alert icon="o-information-circle"
                        class="bg-blue-50 text-blue-700 border-blue-100"
                        title="Akun baru otomatis menggunakan role Admin."
                        description="Anda tidak perlu mengatur hak akses satu per satu. Hak akses akun ini mengikuti role Admin secara otomatis." />

                    <p class="text-sm font-semibold text-gray-700 mt-5 mb-3">
                        Hak Akses Utama
                        <span class="text-xs font-normal text-gray-400">(berdasarkan role Admin)</span>
                    </p>

                    <ul class="space-y-2.5">
                        @foreach ($this->accessList as $access)
                            <li class="flex items-center gap-2.5 text-sm text-gray-600">
                                <span
                                    class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                                    <x-mary-icon name="o-check" class="w-3.5 h-3.5" />
                                </span>
                                {{ $access }}
                            </li>
                        @endforeach
                    </ul>
                </x-mary-card>
            </div>
        </div>
    </x-mary-form>
</div>
