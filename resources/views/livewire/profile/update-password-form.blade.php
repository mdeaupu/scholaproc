<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="space-y-6">
    <header>
        <h3 class="text-lg font-bold text-black flex items-center gap-2">
            <x-mary-icon name="o-key" class="w-5 h-5 text-[#0046FF]" />
            {{ __('Perbarui Kata Sandi') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Pastikan akun Anda menggunakan kata sandi yang panjang dan acak untuk menjaga keamanan.') }}
        </p>
    </header>
    <x-mary-form wire:submit="updatePassword" class="space-y-4">
        <div>
            <label class="block text-sm font-semibold text-black mb-1.5">Kata Sandi Saat Ini</label>
            <x-mary-input wire:model="current_password" type="password" icon="o-lock-closed" placeholder="••••••••"
                autocomplete="current-password"
                class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
        </div>
        <div>
            <label class="block text-sm font-semibold text-black mb-1.5">Kata Sandi Baru</label>
            <x-mary-input wire:model="password" type="password" icon="o-shield-check" placeholder="••••••••"
                autocomplete="new-password"
                class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
        </div>
        <div>
            <label class="block text-sm font-semibold text-black mb-1.5">Konfirmasi Kata Sandi Baru</label>
            <x-mary-input wire:model="password_confirmation" type="password" icon="o-arrow-path" placeholder="••••••••"
                autocomplete="new-password"
                class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
        </div>
        <div class="flex items-center gap-3 pt-2">
            <x-mary-button label="Perbarui Sandi" type="submit" icon="o-arrow-up-on-square"
                class="bg-[#0046FF] hover:bg-[#0038cc] text-white border-none rounded-xl font-bold px-6 shadow-sm"
                spinner="updatePassword" />
            <x-action-message
                class="text-sm font-semibold text-[#0046FF] flex items-center gap-1 bg-[#0046FF]/10 px-3 py-1.5 rounded-lg border border-[#0046FF]/20"
                on="password-updated">
                <x-mary-icon name="o-check-circle" class="w-4 h-4" />
                {{ __('Sandi diperbarui.') }}
            </x-action-message>
        </div>
    </x-mary-form>
</section>