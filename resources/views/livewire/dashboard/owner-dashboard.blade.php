<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Global Owner') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                <div class="p-6 text-gray-900">
                    Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong>. Anda memiliki akses penuh ke
                    sistem.
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 21V5a2 2
                                0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h...\" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Sekolah Aktif Terdaftar</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalSchools }}</p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="p-3 bg-yellow-100 text-yellow-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Menunggu Verifikasi (Baru)</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalSubmittedRequests }}</p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="p-3 bg-green-100 text-green-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pengadaan Selesai</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalCompletedRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <p class="text-sm text-gray-500 font-medium mb-1">Total Nilai Estimasi (Kebutuhan Sekolah)</p>
                    <p class="text-3xl font-extrabold text-gray-800">Rp
                        {{ number_format($grandTotalEstimated, 0, ',', '.') }}</p>
                </div>
                <div class="bg-gradient-to-r from-indigo-50 to-white p-6 rounded-lg shadow-sm border border-indigo-200">
                    <p class="text-sm text-indigo-500 font-medium mb-1">Total Nilai Resmi (Disepakati CV)</p>
                    <p class="text-3xl font-extrabold text-indigo-700">Rp
                        {{ number_format($grandTotalOfficial, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>