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
                    <h1 class="text-2xl font-bold text-gray-900">Edit Data Admin</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Ubah data admin CV sesuai kebutuhan. Pastikan informasi yang diubah benar dan sesuai dengan
                        akses yang diberikan.
                    </p>
                </div>
                <x-mary-button label="Simpan Perubahan" type="submit" icon="o-check" spinner="save"
                    class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-mary-card shadow class="border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-5">Informasi Akun</h2>

                    <div class="flex flex-col sm:flex-row gap-6">
                        <div class="flex flex-col items-center gap-2.5 shrink-0">
                            <x-mary-avatar :image="$this->avatarUrl ?? ''" :placeholder="$this->initials"
                                class="!w-24 !h-24 !bg-[#64748B] text-white [&_span]:!text-2xl" />

                            <input id="admin-avatar" type="file" wire:model="photo"
                                accept="image/png,image/jpeg" class="hidden" />

                            <label for="admin-avatar"
                                class="btn btn-sm btn-outline border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF] hover:border-[#0046FF] hover:text-white">
                                <x-mary-icon name="o-camera" class="w-4 h-4" />
                                Ubah Foto
                            </label>
                            <p class="text-[11px] text-gray-400 text-center">Format JPG, PNG. Maks. 2MB</p>
                            @error('photo')
                                <p class="text-xs text-error text-center">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex-1 min-w-0 space-y-4">
                            <x-mary-input label="Username" wire:model="username" readonly icon="o-user"
                                hint="Username tidak dapat diubah." />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-mary-input label="Nama Lengkap" wire:model="name" :required="true" />
                                <x-mary-input label="Email" wire:model="email" type="email" :required="true" />
                                <x-mary-input label="No. Handphone" wire:model="phone" icon="o-phone"
                                    placeholder="0812xxxxxxx" />
                                <x-mary-input label="Password Baru" wire:model="password" type="password"
                                    icon="o-key" hint="Kosongkan jika tidak ingin mengubah password." />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 mt-5 pt-5 space-y-4">
                        <x-mary-input label="Tanggal Bergabung"
                            :value="$user->created_at?->format('d M Y') ?? '-'" readonly icon="o-calendar" />
                        <x-mary-textarea label="Catatan" wire:model="notes" rows="4"
                            placeholder="Contoh: Penambahan hak akses untuk modul laporan..."
                            hint="Catatan ini hanya dapat dilihat oleh Superadmin." />
                    </div>
                </x-mary-card>
            </div>

            <div class="space-y-6">
                <x-mary-card shadow class="border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-4">Informasi Hak Akses</h2>

                    <x-mary-alert icon="o-information-circle"
                        class="bg-blue-50 text-blue-700 border-blue-100"
                        title="Akun ini menggunakan role Admin."
                        description="Hak akses akun mengikuti role Admin dan tidak dapat diubah melalui halaman ini." />

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

                    <x-mary-alert icon="o-lock-closed" class="mt-5 bg-blue-50 text-blue-700 border-blue-100">
                        Pengelolaan master data hanya dapat dilakukan oleh Superadmin.
                    </x-mary-alert>
                </x-mary-card>

                <x-mary-card shadow class="border border-gray-100">
                    <h2 class="font-bold text-gray-900 mb-4">Riwayat Aktivitas</h2>

                    <dl class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-2.5">
                            <dt class="text-gray-500">Terakhir login</dt>
                            <dd class="font-medium text-gray-700 text-right">
                                {{ $this->lastLoginAt ? \Illuminate\Support\Carbon::createFromTimestamp($this->lastLoginAt)->format('d M Y H:i') : '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-2.5">
                            <dt class="text-gray-500">Dibuat oleh</dt>
                            <dd class="font-medium text-gray-700 text-right">{{ $this->activityHistory['created_by'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-2.5">
                            <dt class="text-gray-500">Tanggal dibuat</dt>
                            <dd class="font-medium text-gray-700 text-right">
                                {{ $this->activityHistory['created_at']?->format('d M Y H:i') ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-2.5">
                            <dt class="text-gray-500">Diperbarui oleh</dt>
                            <dd class="font-medium text-gray-700 text-right">{{ $this->activityHistory['updated_by'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-gray-500">Tanggal diperbarui</dt>
                            <dd class="font-medium text-gray-700 text-right">
                                {{ $this->activityHistory['updated_at']?->format('d M Y H:i') ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </x-mary-card>
            </div>
        </div>

        <x-mary-card shadow class="mt-6 border border-red-100 bg-red-50/40">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-gray-900">Nonaktifkan Akun</h2>
                        @if ($status === 'active')
                            <x-mary-badge value="Aktif"
                                class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                        @else
                            <x-mary-badge value="Nonaktif"
                                class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1 max-w-2xl">
                        Menonaktifkan akun akan mengunci seluruh akses masuk admin ke sistem. Akun yang memiliki riwayat
                        transaksi tidak dapat dihapus dan cukup dinonaktifkan.
                    </p>
                    @if ($user->id === auth()->id())
                        <p class="text-xs text-amber-600 mt-2">
                            Anda tidak dapat menonaktifkan akun sendiri untuk mencegah self-lockout.
                        </p>
                    @endif
                </div>

                @if ($user->id !== auth()->id())
                    @if ($status === 'active')
                        <x-mary-button label="Nonaktifkan" icon="o-lock-closed" wire:click="confirmSuspend"
                            class="btn-sm bg-[#FF8040] hover:bg-[#FF8040]/90 text-white border-none shrink-0" />
                    @else
                        <x-mary-button label="Aktifkan Akun" icon="o-lock-open" wire:click="activate"
                            class="btn-sm bg-green-600 hover:bg-green-700 text-white border-none shrink-0" />
                    @endif
                @endif
            </div>
        </x-mary-card>
    </x-mary-form>

    <x-mary-modal wire:model="confirmingSuspend" title="Nonaktifkan Akun Admin" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-lock-closed" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Nonaktifkan <span
                        class="text-[#FF8040]">"{{ $user->name }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Seluruh akses masuk akun administrasi terkait akan dikunci dan ditangguhkan dari sistem.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingSuspend', false)" class="btn-ghost text-black" />
            <x-mary-button label="Ya, Nonaktifkan" wire:click="suspend"
                class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>

</div>
