<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isOwner()) {
            $this->redirect(route('dashboard.owner', absolute: false), navigate: true);
            return;
        }

        if ($user->isAdminCv()) {
            $this->redirect(route('dashboard.cv', absolute: false), navigate: true);
            return;
        }

        $this->redirect(route('dashboard.school', absolute: false), navigate: true);
    }
}; ?>

<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-8 flex flex-col items-center">
        <h2 class="text-2xl font-black tracking-tight text-black">
            {{ __('Selamat Datang Kembali') }}
        </h2>
        <p class="text-sm text-gray-500 mt-1.5">
            Kelola pengadaan dan kebutuhan sekolah Anda di sini.
        </p>
    </div>
    @if (session('status'))
        <x-mary-alert title="{{ session('status') }}" icon="o-information-circle"
            class="alert-info bg-white text-[#0046FF] border-[#0046FF]/30 mb-6 text-sm rounded-xl border" />
    @endif
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6 sm:p-8 relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-[#0046FF]"></div>
        <x-mary-form wire:submit="login" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-black mb-1.5">
                    Nama Pengguna <span class="text-red-500 font-bold">*</span>
                </label>
                <x-mary-input wire:model="form.username" icon="o-user" placeholder="Masukkan nama pengguna..." required
                    autofocus autocomplete="username"
                    class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
            </div>
            <div>
                <label class="block text-sm font-semibold text-black mb-1.5">
                    Kata Sandi <span class="text-red-500 font-bold">*</span>
                </label>
                <x-mary-input wire:model="form.password" type="password" icon="o-lock-closed"
                    placeholder="Masukkan kata sandi..." required autocomplete="current-password"
                    class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
                @if (Route::has('password.request'))
                    <div class="flex justify-end mt-2">
                        <a class="text-xs font-semibold text-[#FF8040] hover:text-[#0046FF]  hover:underline transition-colors duration-200"
                            href="{{ route('password.request') }}" wire:navigate>
                            {{ __('Lupa Kata Sandi?') }}
                        </a>
                    </div>
                @endif
            </div>
            <div class="pt-4 mt-2 border-t border-gray-100">
                <x-mary-button label="Masuk" type="submit" icon-right="o-arrow-right"
                    class="btn w-full bg-[#0046FF] hover:opacity-90 text-white border-[#0046FF] rounded-xl font-bold tracking-wide shadow-sm transition-all duration-200"
                    spinner="login" />
            </div>
        </x-mary-form>
    </div>
    <p class="text-center text-xs text-gray-400 mt-8">
        Butuh bantuan?
        <a href="https://wa.me/62?text=Halo%20Admin%2C%20saya%20butuh%20bantuan%20akses%20ke%20web%20app%20pengadaan."
            target="_blank" class="text-[#FF8040] hover:text-[#0046FF] font-medium underline transition-colors">
            Hubungi WhatsApp Admin
        </a>
    </p>
</div>