<div>
    <x-mary-header title="Manajemen Admin"
        subtitle="Kelola data admin CV yang memiliki akses ke sistem. Anda dapat menambah, mengubah, menonaktifkan, dan mengatur akses admin." />

    @if (session()->has('message'))
        <div
            class="mb-4 p-4 text-sm font-medium text-[#0046FF] bg-[#0046FF]/10 rounded-xl border border-[#0046FF]/20 flex items-center gap-2">
            <x-mary-icon name="o-check-circle" class="w-5 h-5" />
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-users" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Total Admin</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $stats['active'] }} aktif | {{ $stats['inactive'] }} nonaktif</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <x-mary-icon name="o-check-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Admin Aktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $stats['active'] }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $stats['active_percent'] }}% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                <x-mary-icon name="o-x-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Admin Nonaktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $stats['inactive'] }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $stats['inactive_percent'] }}% dari total</p>
            </div>
        </div>

    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama, email, atau username..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-80 lg:w-[28rem]" />
            <x-mary-select wire:model.live="filterStatus"
                :options="[['id' => '', 'name' => 'Semua Status'], ['id' => 'active', 'name' => 'Aktif'], ['id' => 'suspended', 'name' => 'Nonaktif']]"
                class="w-full sm:w-44" />
            <x-mary-button label="Tambah Admin" link="{{ route('admins.create') }}" icon="o-plus"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full sm:w-auto sm:ml-auto" />
        </div>
    </x-mary-card>

    @php
        $headers = [
            ['key' => 'row_no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'name', 'label' => 'Nama Lengkap', 'class' => 'min-w-56'],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'last_activity', 'label' => 'Terakhir Login'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-16'],
        ];
    @endphp

    <x-mary-card title="Daftar Admin" separator shadow>
        <x-mary-table :headers="$headers" :rows="$admins" container-class="overflow-visible"
            class="w-full">
            @scope('cell_row_no', $admin)
                <span class="text-sm text-gray-400">{{ $admin->row_no }}</span>
            @endscope
            @scope('cell_name', $admin)
                @php
                    $nameParts = preg_split('/\s+/', trim((string) $admin->name));
                    $initials = mb_strtoupper(
                        mb_substr($nameParts[0] ?? '', 0, 1).mb_substr($nameParts[1] ?? '', 0, 1),
                    );
                @endphp
                <x-mary-avatar
                    :image="$admin->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($admin->avatar_path) : ''"
                    :placeholder="$initials" :title="$admin->name" subtitle="Admin"
                    class="!w-9 !h-9 !bg-[#64748B] text-white" />
            @endscope
            @scope('cell_username', $admin)
                <span class="font-mono text-sm text-gray-600">{{ $admin->username }}</span>
            @endscope
            @scope('cell_email', $admin)
                <span class="text-sm text-gray-600">{{ $admin->email ?? '-' }}</span>
            @endscope
            @scope('cell_status', $admin)
                @if ($admin->isActive())
                    <x-mary-badge value="Aktif"
                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                @else
                    <x-mary-badge value="Nonaktif"
                        class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                @endif
            @endscope
            @scope('cell_last_activity', $admin)
                @if ($admin->last_activity)
                    <span class="text-sm text-gray-600 whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::createFromTimestamp($admin->last_activity)->format('d M Y H:i') }}
                    </span>
                @else
                    <span class="text-sm text-gray-400">-</span>
                @endif
            @endscope
            @scope('cell_actions', $admin)
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button"
                                class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF] hover:bg-base-200"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>

                        <x-mary-menu-item title="Lihat Detail" icon="o-eye" class="cursor-pointer"
                            wire:click="show({{ $admin->id }})" />
                        <x-mary-menu-item title="Edit Data" icon="o-pencil-square" route="admins.edit"
                            :route-params="['user' => $admin->id]" />
                        @can('delete', $admin)
                            <x-mary-menu-item title="Hapus" icon="o-trash" class="cursor-pointer text-red-500"
                                wire:click="confirmDestroy({{ $admin->id }})" />
                        @endcan
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($admins->total() > 0)
            <div class="mt-4">{{ $admins->links('pagination.table', ['label' => 'admin']) }}</div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="isDetailModalOpen" title="Detail Admin"
        subtitle="Informasi akun administrator CV" class="backdrop-blur text-black"
        box-class="max-w-3xl" title-class="text-[#0046FF]">
        @if ($selectedUser)
            @php
                $nameParts = preg_split('/\s+/', trim((string) $selectedUser->name));
                $initials = mb_strtoupper(
                    mb_substr($nameParts[0] ?? '', 0, 1).mb_substr($nameParts[1] ?? '', 0, 1),
                );
                $permissions = [
                    'Memverifikasi dan memproses pengajuan kebutuhan sekolah.',
                    'Membuat penawaran harga dan menangani negosiasi.',
                    'Mengelola dokumen administrasi dan BAST pengadaan.',
                    'Memantau status pengiriman, penagihan, dan pembayaran.',
                ];
            @endphp

            <div class="space-y-6">
                <div class="p-5 rounded-2xl border border-gray-100 bg-gradient-to-br from-[#0046FF]/5 to-gray-50">
                    <div class="flex items-center gap-4">
                        <x-mary-avatar
                            :image="$selectedUser->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($selectedUser->avatar_path) : ''"
                            :placeholder="$initials" :alt="$selectedUser->name"
                            class="!w-16 !h-16 !bg-[#0046FF] text-white !text-2xl font-bold" />

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-bold text-gray-900 truncate">{{ $selectedUser->name }}</h3>
                                @if ($selectedUser->isActive())
                                    <x-mary-badge label="Aktif"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                @else
                                    <x-mary-badge label="Nonaktif"
                                        class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Admin</p>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-200/70 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Username</p>
                            <p class="text-sm font-semibold text-gray-800 font-mono mt-0.5">
                                {{ $selectedUser->username }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500">Email</p>
                            <p class="text-sm font-semibold text-gray-800 mt-0.5 break-all">
                                {{ $selectedUser->email ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 flex items-center gap-1">
                                <x-mary-icon name="o-calendar-days" class="w-4 h-4" />
                                Terakhir Login
                            </p>
                            <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                @if ($selectedLastActivity)
                                    {{ \Illuminate\Support\Carbon::createFromTimestamp($selectedLastActivity)->format('d M Y, H:i') }}
                                @else
                                    <span class="text-gray-400 font-normal">Belum pernah login</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <x-mary-tabs wire:model="detailTab">
                    <x-mary-tab name="umum" label="Informasi Umum" icon="o-user">
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                                    Data Pribadi</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Nama Lengkap</p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                            {{ $selectedUser->name }}</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Username</p>
                                        <p class="text-sm font-semibold text-gray-800 font-mono mt-0.5">
                                            {{ $selectedUser->username }}</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Email</p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5 break-all">
                                            {{ $selectedUser->email ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">No. Handphone</p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                            {{ $selectedUser->phone ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Role</p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5">Admin</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Status Akun</p>
                                        <div class="mt-1">
                                            @if ($selectedUser->isActive())
                                                <x-mary-badge label="Aktif"
                                                    class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                            @else
                                                <x-mary-badge label="Nonaktif"
                                                    class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Dibuat Pada</p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                            {{ $selectedUser->created_at?->format('d M Y, H:i') ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <p class="text-xs text-gray-500">Diperbarui Pada</p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                            {{ $selectedUser->updated_at?->format('d M Y, H:i') ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                                    Hak Akses</h4>
                                <x-mary-alert icon="o-information-circle"
                                    class="bg-blue-50 text-blue-800 border border-blue-200 text-sm"
                                    title="Ringkasan hak akses Admin"
                                    description="Admin CV bertanggung jawab memverifikasi pengajuan, membuat penawaran, menangani negosiasi, serta memproses dokumen dan pengadaan bersama sekolah." />
                            </div>
                        </div>
                    </x-mary-tab>

                    <x-mary-tab name="akses" label="Hak Akses" icon="o-shield-check">
                        <div class="space-y-4">
                            <x-mary-alert icon="o-key"
                                class="bg-indigo-50 text-indigo-800 border border-indigo-200 text-sm"
                                title="Peran Admin di sistem"
                                description="Hak akses berikut melekat pada role dan tidak dapat diubah melalui modal ini." />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($permissions as $permission)
                                    <div
                                        class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                        <span class="p-1.5 bg-green-100 text-green-600 rounded-full shrink-0">
                                            <x-mary-icon name="o-check" class="w-4 h-4" />
                                        </span>
                                        <span class="text-sm text-gray-700 leading-relaxed">{{ $permission }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-mary-tab>

                    <x-mary-tab name="riwayat" label="Riwayat Aktivitas" icon="o-clock">
                        @if (empty($selectedActivities))
                            <div class="text-center py-10 text-gray-400">
                                <x-mary-icon name="o-clock" class="w-10 h-10 mx-auto mb-2" />
                                <p class="text-sm">Belum ada aktivitas yang tercatat untuk akun ini.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($selectedActivities as $activity)
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="p-2 bg-[#0046FF]/10 text-[#0046FF] rounded-full shrink-0 mt-0.5">
                                            <x-mary-icon name="o-clipboard-document-list" class="w-4 h-4" />
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ $activity['label'] }}</p>
                                                <p class="text-xs text-gray-400 whitespace-nowrap">
                                                    {{ $activity['created_at'] }}</p>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Dilakukan oleh: {{ $activity['causer'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-mary-tab>
                </x-mary-tabs>
            </div>
        @endif
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
                    Akun akan dihapus (soft delete) dari sistem dan tidak dapat dipulihkan secara langsung. Akun yang
                    memiliki riwayat transaksi tidak dapat dihapus dan harus dinonaktifkan.
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
