<div>
    <x-mary-header title="{{ __('Dashboard Global Owner') }}" separator />
    <div class="space-y-6">
        <x-mary-card shadow class="p-0 border-l-4 border-[#0046FF]">
            <div class="p-6 text-black">
                Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong>. Anda memiliki akses penuh ke
                sistem.
            </div>
        </x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-mary-card shadow class="hover:border-[#0046FF] transition-colors">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-[#0046FF]/10 text-[#0046FF] rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h..."></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Sekolah Aktif Terdaftar</p>
                        <p class="text-3xl font-bold text-black">{{ $totalSchools }}</p>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card shadow class="hover:border-[#FF8040] transition-colors">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Menunggu Verifikasi (Baru)</p>
                        <p class="text-3xl font-bold text-black">{{ $totalSubmittedRequests }}</p>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card shadow class="hover:border-black transition-colors">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-black/5 text-black rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Selesai</p>
                        <p class="text-2xl font-bold text-black">{{ $totalCompletedRequests }}</p>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-mary-card shadow class="border-t-4 border-black">
                <p class="text-sm text-gray-500 font-medium mb-1">Total Nilai Estimasi (Kebutuhan Sekolah)</p>
                <p class="text-3xl font-extrabold text-black">Rp {{ number_format($grandTotalEstimated, 0, ',', '.') }}
                </p>
            </x-mary-card>
            <x-mary-card shadow class="border-t-4 border-[#0046FF]">
                <p class="text-sm text-[#0046FF] font-medium mb-1">Total Nilai Resmi (Disepakati CV)</p>
                <p class="text-3xl font-extrabold text-[#0046FF]">Rp
                    {{ number_format($grandTotalOfficial, 0, ',', '.') }}
                </p>
            </x-mary-card>
        </div>
    </div>
</div>