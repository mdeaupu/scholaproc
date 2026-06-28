<div>
    <x-mary-header title="Manajemen Admin" subtitle="Daftar admin yang berwenang memproses pengajuan pengadaan barang."
        separator>
        <x-slot:actions>
            <x-mary-button label="Tambah Admin CV" wire:click="create" icon="o-plus"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
        </x-slot:actions>
    </x-mary-header>
    @if (session()->has('message'))
        <div
            class="mb-4 p-4 text-sm font-medium text-[#0046FF] bg-[#0046FF]/10 rounded-xl border border-[#0046FF]/20 flex items-center gap-2">
            <x-mary-icon name="o-check-circle" class="w-5 h-5" />
            {{ session('message') }}
        </div>
    @endif
    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama atau username..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-56" />
            <x-mary-select wire:model.live="filterStatus" :options="[['id' => 'active', 'name' => 'Aktif'], ['id' => 'suspended', 'name' => 'Dibekukan']]" placeholder="Semua Status" class="w-40" />
        </div>
    </x-mary-card>
    @php
        $headers = [
            ['key' => 'name', 'label' => 'Nama Lengkap'],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'text-center'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right'],
        ];
    @endphp
    <x-mary-card shadow class="border-t-4 border-[#0046FF]">
        <x-mary-table :headers="$headers" :rows="$admins" with-pagination>
            @scope('cell_name', $admin)
                <span class="font-medium text-black">{{ $admin->name }}</span>
            @endscope
            @scope('cell_username', $admin)
                <span class="font-mono text-sm text-gray-500">{{ $admin->username }}</span>
            @endscope
            @scope('cell_status', $admin)
                @if ($admin->isActive())
                    <x-mary-badge label="Aktif" class="bg-[#0046FF] text-white border-none badge-sm" />
                @else
                    <x-mary-badge label="Suspended" class="bg-[#FF8040] text-white border-none badge-sm" />
                @endif
            @endscope
            @scope('cell_actions', $admin)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-eye" wire:click="show({{ $admin->id }})"
                        class="btn-sm btn-ghost text-[#0046FF]" tooltip="Lihat Detail" />
                    <x-mary-button icon="o-pencil-square" wire:click="edit({{ $admin->id }})"
                        class="btn-sm btn-ghost text-black" tooltip="Ubah Data" />
                    <x-mary-button icon="o-key"
                        wire:click="confirmReset({{ $admin->id }}, '{{ addslashes($admin->name) }}')"
                        class="btn-sm btn-ghost text-amber-500" tooltip="Reset Password" />
                    @if ($admin->isActive())
                        <x-mary-button icon="o-lock-closed"
                            wire:click="confirmSuspend({{ $admin->id }}, '{{ addslashes($admin->name) }}')"
                            class="btn-sm btn-ghost text-[#FF8040]" tooltip="Bekukan" />
                    @else
                        <x-mary-button icon="o-lock-open" wire:click="activate({{ $admin->id }})"
                            class="btn-sm btn-ghost text-[#0046FF]" tooltip="Aktifkan" />
                    @endif
                    <x-mary-button icon="o-trash"
                        wire:click="confirmDestroy({{ $admin->id }}, '{{ addslashes($admin->name) }}')"
                        class="btn-sm btn-ghost text-[#FF8040]" tooltip="Hapus" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
    <x-mary-modal wire:model="isFormModalOpen"
        title="{{ $isEditMode ? 'Edit Data Admin CV' : 'Tambah Admin CV Baru' }}" class="backdrop-blur"
        title-class="text-[#0046FF]">
        <div class="space-y-4">
            <span tabindex="0" autofocus class="absolute opacity-0 pointer-events-none"></span>
            <x-mary-input label="Nama Lengkap" wire:model="name" placeholder="Contoh: Budi Santoso" />
            <x-mary-input label="Username" wire:model="username" placeholder="Contoh: budi_cv" />
            <x-mary-input label="Email (Opsional)" wire:model="email" type="email"
                placeholder="Contoh: budi@gmail.com" />
            <x-mary-input label="Password {{ $isEditMode ? '(Kosongkan jika tidak diganti)' : '' }}"
                wire:model="password" type="password" placeholder="Masukkan password" />
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('isFormModalOpen', false)" class="btn-ghost text-black" />
            <x-mary-button label="{{ $isEditMode ? 'Simpan Perubahan' : 'Simpan Admin' }}"
                wire:click="{{ $isEditMode ? 'update' : 'store' }}"
                class="bg-[#0046FF] text-white hover:bg-[#0046FF]/90 border-none" spinner />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="isDetailModalOpen" title="Detail Akun Admin CV" class="backdrop-blur text-black"
        box-class="max-w-md" title-class="text-[#0046FF]">
        @if ($selectedUser)
            <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm border border-gray-100">
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500 font-medium">Nama Lengkap</span>
                    <span class="font-bold">{{ $selectedUser->name }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500 font-medium">Username</span>
                    <span class="font-mono">{{ $selectedUser->username }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500 font-medium">Email Resmi</span>
                    <span>{{ $selectedUser->email ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500 font-medium">Sistem Role</span>
                    <span class="uppercase font-bold text-gray-700">{{ $selectedUser->role }}</span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-gray-500 font-medium">Status</span>
                    @if ($selectedUser->isActive())
                        <x-mary-badge label="Aktif" class="bg-[#0046FF] text-white border-none badge-sm" />
                    @else
                        <x-mary-badge label="Dibekukan" class="bg-[#FF8040] text-white border-none badge-sm" />
                    @endif
                </div>
            </div>
        @endif
        <x-slot:actions>
            <x-mary-button label="Tutup" wire:click="$set('isDetailModalOpen', false)"
                class="btn-ghost text-black" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingReset" title="Reset Password" class="backdrop-blur"
        title-class="text-amber-500">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-amber-500/10 text-amber-500 rounded-full shrink-0">
                <x-mary-icon name="o-key" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Reset password <span
                        class="text-amber-500">"{{ $targetAdminName ?? '' }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Password akun akan dikembalikan ke nilai default sistem: <strong>Password123!</strong>
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingReset', false)" class="btn-ghost text-black" />
            <x-mary-button label="Ya, Reset" wire:click="resetPasswordAction"
                class="bg-amber-500 text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingSuspend" title="Bekukan Akun Admin CV" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-lock-closed" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Bekukan <span
                        class="text-[#FF8040]">"{{ $targetAdminName ?? '' }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Seluruh akses masuk akun administrasi terkait akan dikunci dan ditangguhkan dari sistem.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingSuspend', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Bekukan" wire:click="suspend" class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingDestroy" title="Hapus Akun Admin CV" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-trash" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Hapus <span
                        class="text-[#FF8040]">"{{ $targetAdminName ?? '' }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Seluruh data terkait akun ini akan dihapus (Soft Delete) dari sistem dan tidak dapat dipulihkan
                    secara langsung.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDestroy', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="destroy" class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
</div>
