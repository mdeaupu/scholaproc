<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="space-y-6">
    <header>
        <h3 class="text-lg font-bold text-black flex items-center gap-2">
            <x-mary-icon name="o-user" class="w-5 h-5 text-[#0046FF]" />
            {{ __('Informasi Profil') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ __("Perbarui data nama lengkap dan alamat email utama akun Anda.") }}
        </p>
    </header>
    <x-mary-form wire:submit="updateProfileInformation" class="space-y-4">
        <div>
            <label class="block text-sm font-semibold text-black mb-1.5">Nama Lengkap</label>
            <x-mary-input wire:model="name" icon="o-identification" placeholder="Masukkan nama lengkap..." required
                autofocus autocomplete="name"
                class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
        </div>
        <div>
            <label class="block text-sm font-semibold text-black mb-1.5">Alamat Email</label>
            <x-mary-input wire:model="email" type="email" icon="o-envelope" placeholder="nama@email.com" required
                autocomplete="username"
                class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#0046FF] focus:ring-[#0046FF] rounded-xl" />
            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-[#FF8040]/10 rounded-xl border border-[#FF8040]/20">
                    <p class="text-xs text-[#FF8040] flex flex-col sm:flex-row sm:items-center gap-2">
                        <span>{{ __('Alamat email Anda belum terverifikasi.') }}</span>
                        <button wire:click.prevent="sendVerification" class="underline font-bold hover:text-[#e67339]">
                            {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1.5 font-semibold text-xs text-[#0046FF]">
                            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
        <div class="flex items-center gap-3 pt-2">
            <x-mary-button label="Simpan Perubahan" type="submit" icon="o-check"
                class="bg-[#0046FF] hover:bg-[#0038cc] text-white border-none rounded-xl font-bold px-6 shadow-sm"
                spinner="updateProfileInformation" />
            <x-action-message
                class="text-sm font-semibold text-[#0046FF] flex items-center gap-1 bg-[#0046FF]/10 px-3 py-1.5 rounded-lg border border-[#0046FF]/20"
                on="profile-updated">
                <x-mary-icon name="o-check-circle" class="w-4 h-4" />
                {{ __('Berhasil disimpan.') }}
            </x-action-message>
        </div>
    </x-mary-form>
</section>