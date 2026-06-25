<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h3 class="text-lg font-bold text-black flex items-center gap-2">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-[#FF8040]" />
            {{ __('Hapus Akun Permanen') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Setelah akun dihapus, seluruh aset dan basis data log kerja internal Anda akan dihapus secara permanen dari server utama.') }}
        </p>
    </header>
    <x-mary-button label="Hapus Akun Saya" icon="o-trash" wire:click="$set('confirmingUserDeletion', true)"
        class="bg-[#FF8040] hover:bg-[#e67339] border-none text-white font-bold rounded-xl shadow-sm px-5" />
    <x-mary-modal wire:model="confirmingUserDeletion" title="Konfirmasi Penghapusan Akun" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <form wire:submit="deleteUser" class="space-y-5 p-1">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                    <x-mary-icon name="o-exclamation-circle" class="w-8 h-8" />
                </div>
                <div>
                    <p class="font-semibold text-black">
                        Apakah Anda benar-benar yakin?
                    </p>
                    <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                        Tindakan ini tidak bisa dibatalkan. Silakan masukkan kata sandi rahasia Anda untuk melakukan
                        otentikasi penghapusan final.
                    </p>
                </div>
            </div>
            <div class="pt-2 border-t border-gray-100">
                <label class="block text-sm font-semibold text-black mb-1.5">Masukkan Kata Sandi Anda</label>
                <x-mary-input wire:model="password" type="password" icon="o-lock-closed" placeholder="••••••••" required
                    class="input-md bg-gray-50/50 border-gray-200 text-black placeholder-gray-400 focus:bg-white focus:border-[#FF8040] focus:ring-[#FF8040] rounded-xl" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <x-mary-button label="Batal" wire:click="$set('confirmingUserDeletion', false)"
                    class="btn-ghost rounded-xl text-black" />
                <x-mary-button label="Ya, Hapus Akun" type="submit" icon="o-trash"
                    class="bg-[#FF8040] hover:bg-[#e67339] border-none text-white shadow-sm rounded-xl font-bold"
                    spinner="deleteUser" />
            </div>
        </form>
    </x-mary-modal>
</section>