<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="flex flex-col h-full">
    <div class="m-3 p-3 bg-base-200 rounded-xl flex items-center gap-3">
        @php
            $user = auth()->user();
            $identity = $user?->isSchool() && $user->school?->name ? $user->school->name : $user->name ?? '';
            $initials = collect(explode(' ', trim($identity)))
                ->filter()
                ->take(2)
                ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->join('');
            $roleLabel = match (true) {
                $user?->isSuperAdmin() => 'Superadmin',
                $user?->isAdmin() => 'Admin CV',
                default => 'Sekolah',
            };
        @endphp
        <div class="avatar placeholder shrink-0">
            <div class="w-10 rounded-xl bg-[#0046FF] text-white">
                <span class="font-bold">{{ $initials ?: '?' }}</span>
            </div>
        </div>
        <div class="min-w-0">
            <p class="font-bold text-sm text-gray-900 truncate">{{ $identity ?: 'Pengguna' }}</p>
            <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
        </div>
    </div>

    <x-mary-menu activate-by-route>
        <x-mary-menu-item title="Dashboard" icon="o-squares-2x2" route="dashboard" :active="request()->routeIs('dashboard*')" />
        <x-mary-menu-item title="Pengajuan Pengadaan" icon="o-shopping-cart" route="procurement.index"
            :active="request()->routeIs('procurement.*')" />
        @if (auth()->user()?->isSuperAdmin())
            <x-mary-menu-item title="Manajemen Sekolah" icon="o-academic-cap" route="schools.index" :active="request()->routeIs('schools.*')" />
        @endif
        @if (auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin())
            <x-mary-menu-item title="Manajemen Supplier" icon="o-truck" :route="auth()->user()->isAdmin() ? 'admin.suppliers.index' : 'superadmin.suppliers.index'" :active="request()->routeIs('*suppliers.*')" />
        @endif
        @if (auth()->user()?->isSuperAdmin())
            <x-mary-menu-item title="Manajemen Admin" icon="o-users" route="admins.index" :active="request()->routeIs('admins.*')" />
        @endif
        @if (auth()->user()?->isSuperAdmin())
            <x-mary-menu-sub title="Master Data" icon="o-circle-stack" :open="request()->routeIs('master.*')">
                <x-mary-menu-item title="Kategori Paket" icon="o-tag" route="master.package-categories"
                    :active="request()->routeIs('master.package-categories')" />
                <x-mary-menu-item title="Sumber Dana" icon="o-banknotes" route="master.funding-sources"
                    :active="request()->routeIs('master.funding-sources')" />
                <x-mary-menu-item title="Tahun Anggaran" icon="o-calendar-days" route="master.budget-years"
                    :active="request()->routeIs('master.budget-years')" />
                <x-mary-menu-item title="Satuan Barang" icon="o-scale" route="master.item-units" :active="request()->routeIs('master.item-units')" />
            </x-mary-menu-sub>
        @endif
        <x-mary-menu-item title="Profil" icon="o-user-circle" route="profile" />
    </x-mary-menu>

    <div class="mt-auto p-3">
        <button wire:click="logout"
            class="w-full flex items-center gap-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-base-200 hover:text-[#0046FF] transition-colors">
            <x-mary-icon name="o-arrow-left-on-rectangle" class="w-5 h-5" />
            <span class="font-semibold text-sm">Logout</span>
        </button>
    </div>
</div>
