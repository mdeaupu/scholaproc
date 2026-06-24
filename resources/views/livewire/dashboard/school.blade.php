<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Institusi: ') . $schoolName }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Dalam Proses</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeCount }}</p>
                    </div>
                    <div class="p-4 bg-indigo-50 rounded-full text-indigo-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Riwayat Selesai</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $completedCount }}</p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-full text-green-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">5 Pengajuan Terakhir Anda</h3>
                    <a href="#" class="text-sm text-indigo-600 hover:underline">Buat Pengajuan Baru</a>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-gray-200">
                        @forelse ($recentRequests as $request)
                            <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $request->request_number ?? 'Draft Baru' }}</p>
                                    <p class="text-sm text-gray-500">Estimasi Kebutuhan: Rp
                                        {{ number_format($request->total_estimated_amount ?? 0, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $request->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
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
            </div>
        </div>
    </div>
</x-app-layout>