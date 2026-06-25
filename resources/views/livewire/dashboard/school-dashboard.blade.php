<div>
    <x-mary-header title="{{ __('Dasbor Institusi: ') . $schoolName }}" separator />
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-mary-card shadow class="hover:border-[#0046FF] transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Dalam Proses</p>
                        <p class="text-3xl font-bold text-black mt-1">{{ $activeCount }}</p>
                    </div>
                    <div class="p-4 bg-[#0046FF]/10 rounded-full text-[#0046FF]">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card shadow class="hover:border-black transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Telah Selesai</p>
                        <p class="text-3xl font-bold text-black mt-1">{{ $completedCount }}</p>
                    </div>
                    <div class="p-4 bg-black/5 rounded-full text-black">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <x-mary-card shadow class="p-0 overflow-hidden border-t-4 border-[#0046FF]">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-black">5 Pengajuan Terakhir</h3>
            </div>
            <div class="bg-white">
                <ul class="divide-y divide-gray-200">
                    @forelse ($recentRequests as $request)
                        <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-[#0046FF]">{{ $request->request_number ?? 'Draft Baru' }}</p>
                                <p class="text-sm text-gray-500">Estimasi Kebutuhan: Rp
                                    {{ number_format($request->total_estimated_amount ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        {{ $request->status === 'completed' ? 'bg-black text-white' : 'bg-[#FF8040] text-white' }}">
                                    {{ strtoupper($request->status) }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-gray-500">
                            Belum ada riwayat pengajuan barang. Mulai lengkapi infrastruktur sekolah Anda!
                        </li>
                    @endforelse
                </ul>
            </div>
        </x-mary-card>
    </div>
</div>