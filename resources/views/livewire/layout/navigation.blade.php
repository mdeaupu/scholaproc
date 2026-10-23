<?php

use App\Models\Notification;
use Livewire\Volt\Component;

new class extends Component {
    public $notifications = [];

    public function mount(): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($user?->isSchool() && $user->school_id) {
            $this->notifications = Notification::whereHas('procurementRequest', fn($query) => $query->where('school_id', $user->school_id))->latest()->take(5)->get();
        }
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
        @if (auth()->user()?->isSchool())
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
                </div>
            </x-mary-dropdown>
        @endif
    </x-slot:actions>
</x-mary-nav>
