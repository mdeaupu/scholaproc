<?php

use App\Livewire\Actions\Logout;
use App\Models\Notification;
use Livewire\Volt\Component;

new class extends Component {
    public $notifications = [];

    public function getUnreadCountProperty(): int
    {
        $user = auth()->user();

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return Notification::unread()->count();
        }

        return Notification::unread()
            ->whereIn('event_type', Notification::SCHOOL_EVENTS)
            ->whereHas('procurementRequest', fn ($q) => $q->where('school_id', $user->school_id))
            ->count();
    }

    #[On('profile-updated')]
    public function updateSidebar() {}

    public function mount(): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($user?->isSchool() && $user->school_id) {
            $this->notifications = Notification::whereHas('procurementRequest', fn($query) => $query->where('school_id', $user->school_id))->latest()->take(5)->get();
        }
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-mary-nav sticky :full-width="true">
    <x-slot:brand>
        <label for="sidebar" class="btn btn-square btn-ghost swap swap-rotate lg:hidden">
            <input type="checkbox" />
            <svg class="swap-off fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg class="swap-on fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </label>

        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
            <span class="bg-[#0046FF] text-white rounded-xl p-2">
                <x-mary-icon name="o-building-library" class="w-5 h-5" />
            </span>
            <span class="leading-tight text-left">
                <span class="block font-extrabold text-sm sm:text-base text-gray-900">Sistem Informasi</span>
                <span class="block text-xs sm:text-sm text-gray-500">Pengadaan Barang Sekolah</span>
            </span>
        </a>
    </x-slot:brand>

    <x-slot:actions>
        @php
            $user = auth()->user();
        @endphp

        @if ($user?->isSchool())
            <x-mary-dropdown right scroll max-height="max-h-96">
                <x-slot:trigger>
                    <button class="btn btn-ghost btn-circle relative" title="Notifikasi">
                        <x-mary-icon name="o-bell-alert" class="w-6 h-6 text-gray-600" />
                        @if (count($notifications))
                            <span
                                class="badge badge-error badge-xs absolute top-0 right-0 -mt-1 -me-1 text-white font-bold">
                                {{ count($notifications) > 9 ? '9+' : count($notifications) }}
                            </span>
                        @endif
                    </button>
                </x-slot:trigger>

                <div class="w-80">
                    <div class="px-4 py-2.5 font-bold text-sm text-gray-900 border-b border-base-content/10">
                        Notifikasi Terbaru
                    </div>
                    @forelse ($notifications as $notification)
                        <li>
                            <div class="flex flex-col items-start gap-1 py-2 px-2">
                                <span class="text-sm text-gray-700 leading-snug normal-case">
                                    {{ \Illuminate\Support\Str::limit($notification->message_content, 80) }}</span>
                                <span
                                    class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        </li>
                    @empty
                        <li>
                            <div class="text-sm text-gray-400 normal-case py-2 px-2">Belum ada notifikasi.</div>
                        </li>
                    @endforelse
                    <li class="border-t border-base-content/10 mt-1">
                        <a href="#notifikasi-terbaru" class="text-[#0046FF] font-semibold justify-center text-sm">
                            Lihat Semua
                        </a>
                    </li>

                    @if ($user->isSuperAdmin() || $user->isAdmin())
                        <x-mary-menu-sub title="Master Data" icon="o-circle-stack" class="text-sm font-medium text-black"
                            :open="request()->routeIs('admins.*', 'schools.*', '*suppliers.index', 'master.*')">
                            @if ($user->isSuperAdmin())
                                <x-mary-menu-item title="Manajemen Admin" icon="o-users" link="{{ route('admins.index') }}"
                                    :active="request()->routeIs('admins.*')" wire:navigate
                                    class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />

                                <x-mary-menu-item title="Manajemen Sekolah" icon="o-academic-cap" link="{{ route('schools.index') }}"
                                    :active="request()->routeIs('schools.*')" wire:navigate
                                    class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />

                                <x-mary-menu-item title="Manajemen Supplier" icon="o-building-office-2"
                                    link="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')" wire:navigate
                                    class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                            @endif

                            <x-mary-menu-item title="Kategori Paket" icon="o-tag" link="{{ route('master.package-categories') }}"
                                :active="request()->routeIs('master.package-categories')" wire:navigate
                                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                            <x-mary-menu-item title="Sumber Dana" icon="o-banknotes" link="{{ route('master.funding-sources') }}"
                                :active="request()->routeIs('master.funding-sources')" wire:navigate
                                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                            <x-mary-menu-item title="Tahun Anggaran" icon="o-calendar-days" link="{{ route('master.budget-years') }}"
                                :active="request()->routeIs('master.budget-years')" wire:navigate
                                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                            <x-mary-menu-item title="Satuan Barang" icon="o-scale" link="{{ route('master.item-units') }}"
                                :active="request()->routeIs('master.item-units')" wire:navigate
                                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                        </x-mary-menu-sub>
                    @endif
                    <x-mary-menu-item title="Data Pengadaan" icon="o-shopping-cart" link="{{ route('procurement.index') }}"
                        :active="request()->routeIs('procurement.*')" wire:navigate class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                    @if ($user->isSuperAdmin() || $user->isAdmin())
                        <x-mary-menu-item title="Notifikasi" icon="o-bell" link="{{ route('notifications.index') }}"
                            :active="request()->routeIs('notifications.index')" wire:navigate
                            class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]"
                            badge="{{ $this->unreadCount > 0 ? $this->unreadCount : '' }}" />
                    @else
                        <x-mary-menu-item title="Notifikasi" icon="o-bell" link="{{ route('notifications.inbox') }}"
                            :active="request()->routeIs('notifications.inbox')" wire:navigate
                            class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]"
                            badge="{{ $this->unreadCount > 0 ? $this->unreadCount : '' }}" />
                    @endif
                    @if ($user->isSuperAdmin())
                        <x-mary-menu-item title="WhatsApp" icon="o-chat-bubble-left-right" link="{{ route('whatsapp.session') }}"
                            :active="request()->routeIs('whatsapp.*')" wire:navigate
                            class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                    @endif
                    <div class="my-2 border-t border-gray-200"></div>
                    <x-mary-menu-sub title="Pengaturan" icon="o-cog-6-tooth" class="text-sm font-medium text-black"
                        :open="request()->routeIs('profile*')">
                        <x-mary-menu-item title="Profil Saya" icon="o-user" link="{{ route('profile') }}" :active="request()->routeIs('profile*')"
                            wire:navigate class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
                    </x-mary-menu-sub>
                </div>
            </x-mary-dropdown>
        @endif
        <x-mary-menu-item title="Data Pengadaan" icon="o-shopping-cart" link="{{ route('procurement.index') }}"
            :active="request()->routeIs('procurement.*')" wire:navigate class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
        @if ($user->isSuperAdmin() || $user->isAdmin())
            <x-mary-menu-item title="Laporan" icon="o-document-chart-bar" link="{{ route('reports.index') }}"
                :active="request()->routeIs('reports.*')" wire:navigate
                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
            <x-mary-menu-item title="Notifikasi" icon="o-bell" link="{{ route('notifications.index') }}"
                :active="request()->routeIs('notifications.index')" wire:navigate
                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]"
                badge="{{ $this->unreadCount > 0 ? $this->unreadCount : '' }}" />
        @else
            <x-mary-menu-item title="Notifikasi" icon="o-bell" link="{{ route('notifications.inbox') }}"
                :active="request()->routeIs('notifications.inbox')" wire:navigate
                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]"
                badge="{{ $this->unreadCount > 0 ? $this->unreadCount : '' }}" />
        @endif
        @if ($user->isSuperAdmin())
            <x-mary-menu-item title="WhatsApp" icon="o-chat-bubble-left-right" link="{{ route('whatsapp.session') }}"
                :active="request()->routeIs('whatsapp.*')" wire:navigate
                class="rounded-lg text-sm font-medium text-black hover:text-[#0046FF]" />
        @endif
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
            </div>
        </x-mary-modal>
    </template>
</x-mary-nav>
