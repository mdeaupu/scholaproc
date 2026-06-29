<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $logoutModal = false;

    #[On('profile-updated')]
    public function updateSidebar() {}

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    @php
        $user = auth()->user();
        $initials = $user
            ? collect(explode(' ', $user->name))
                ->map(fn($n) => mb_substr($n, 0, 1))
                ->take(2)
                ->join('')
            : 'US';
        $dashboardRoute = 'dashboard';
        if ($user) {
            if ($user->isOwner()) {
                $dashboardRoute = 'dashboard.owner';
            } elseif (method_exists($user, 'isAdminCv') && $user->isAdminCv()) {
                $dashboardRoute = 'dashboard.cv';
            } else {
                $dashboardRoute = 'dashboard.school';
            }
        }
    @endphp
    <x-mary-menu class="px-3 py-3 gap-1">
        @if ($user)
            <x-mary-list-item :item="$user" value="name" no-separator no-hover
                class="border-b border-gray-200 pb-4 mb-3 -mx-1 text-black">
                <x-slot:avatar>
                    <div class="avatar placeholder">
                        <div
                            class="bg-[#0046FF]/10 text-[#0046FF] w-9 rounded-lg font-bold text-xs tracking-wider flex items-center justify-center">
                            <span>{{ strtoupper($initials) }}</span>
                        </div>
                    </div>
                </x-slot:avatar>
                <x-slot:actions>
                    <x-mary-button icon="o-power"
                        class="btn-circle btn-ghost btn-xs text-[#FF8040]/80 hover:text-[#FF8040] hover:bg-[#FF8040]/10"
                        tooltip-left="Log Out" no-wire-navigate @click="$wire.logoutModal = true" />
                </x-slot:actions>
            </x-mary-list-item>
        @endif
        <x-mary-menu-item title="Dashboard" icon="o-squares-2x2" link="{{ route($dashboardRoute) }}" :active="request()->routeIs('dashboard*')"
            wire:navigate class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
        @if ($user && $user->isOwner())
            <x-mary-menu-sub title="Master Data" icon="o-circle-stack" :open="request()->routeIs('admins.*', 'schools.*', '*suppliers.index')">
                <x-mary-menu-item title="Manajemen Admin" icon="o-users" link="{{ route('admins.index') }}"
                    :active="request()->routeIs('admins.*')" wire:navigate
                    class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                <x-mary-menu-item title="Manajemen Sekolah" icon="o-academic-cap" link="{{ route('schools.index') }}"
                    :active="request()->routeIs('schools.*')" wire:navigate
                    class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                <x-mary-menu-item title="Manajemen Supplier" icon="o-building-office-2"
                    link="{{ route('owner.suppliers.index') }}" :active="request()->routeIs('owner.suppliers.*')" wire:navigate
                    class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
            </x-mary-menu-sub>
        @endif
        <x-mary-menu-item title="Data Pengadaan" icon="o-shopping-cart" link="{{ route('procurement.index') }}"
            :active="request()->routeIs('procurement.*')" wire:navigate class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
        <div class="my-2 border-t border-gray-200"></div>
        <x-mary-menu-sub title="Pengaturan" icon="o-cog-6-tooth" class="text-sm font-medium text-black"
            :open="request()->routeIs('profile*')">
            <x-mary-menu-item title="Profil Saya" icon="o-user" link="{{ route('profile') }}" :active="request()->routeIs('profile*')"
                wire:navigate class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
        </x-mary-menu-sub>
    </x-mary-menu>
    <template x-teleport="body">
        <x-mary-modal wire:model="logoutModal" title="Konfirmasi Keluar" class="backdrop-blur"
            title-class="text-[#FF8040]">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                    <x-mary-icon name="o-power" class="w-7 h-7" />
                </div>
                <div>
                    <p class="font-semibold text-black">Keluar dari <span class="text-[#FF8040]">Aplikasi</span>?</p>
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                        Sesi Anda akan diakhiri dan Anda harus masuk kembali untuk mengakses sistem.
                    </p>
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.logoutModal = false" class="btn-ghost text-black" />
                <x-mary-button label="Ya, Keluar" wire:click="logout"
                    class="bg-[#FF8040] text-white hover:bg-[#e67339] border-none" />
            </x-slot:actions>
        </x-mary-modal>
    </template>
</div>
