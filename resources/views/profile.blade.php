<x-app-layout>
    <x-mary-header title="{{ __('Pengaturan Profile Saya') }}" separator />
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-base-200/80 p-6 sm:p-8 relative">
            <div class="absolute top-0 left-0 w-1 h-full bg-[#0046FF]"></div>
            <div class="max-w-2xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-base-200/80 p-6 sm:p-8 relative">
            <div class="absolute top-0 left-0 w-1 h-full bg-[#0046FF]"></div>
            <div class="max-w-2xl">
                <livewire:profile.update-password-form />
            </div>
        </div>
    </div>
</x-app-layout>
