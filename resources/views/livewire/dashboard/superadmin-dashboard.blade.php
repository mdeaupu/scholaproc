<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Global Owner') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="filter-budget-year" class="block text-xs font-medium text-gray-500 mb-1">Tahun
                            Anggaran</label>
                        <select id="filter-budget-year" wire:model.live="budgetYearId"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Tahun Anggaran</option>
                            @foreach ($budgetYears as $year)
                                <option value="{{ $year->id }}">
                                    {{ $year->name }}{{ $year->is_active ? ' (aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-school" class="block text-xs font-medium text-gray-500 mb-1">Sekolah</label>
                        <select id="filter-school" wire:model.live="schoolId"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Sekolah</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-date-from" class="block text-xs font-medium text-gray-500 mb-1">Dari
                            Tanggal</label>
                        <input id="filter-date-from" type="date" wire:model.live="dateFrom"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="filter-date-to" class="block text-xs font-medium text-gray-500 mb-1">Sampai
                            Tanggal</label>
                        <input id="filter-date-to" type="date" wire:model.live="dateTo"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="button" wire:click="resetFilters"
                        class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                        Reset Filter
                    </button>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                <div class="p-6 text-gray-900">
                    Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong>. Anda memiliki akses penuh ke
                    sistem.
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Sekolah Aktif</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalSchools }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Pengguna Aktif</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalActiveUsers }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $totalAdminUsers }} admin &middot;
                        {{ $totalSchoolUsers }} sekolah</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Total Pengajuan</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalRequests }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Menunggu Verifikasi</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalSubmittedRequests }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Pengadaan Selesai</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalCompletedRequests }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Status Pengadaan</h3>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach (\App\Models\ProcurementRequest::STATUS_LABELS as $status => $label)
                        <div
                            class="rounded-lg border border-gray-200 px-4 py-3 flex items-center justify-between {{ \App\Models\ProcurementRequest::STATUS_PILL_CLASSES[$status] }}">
                            <span class="text-xs font-semibold">{{ $label }}</span>
                            <span class="text-xl font-bold">{{ $statusBreakdown[$status] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-linear-to-r from-gray-50 to-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <p class="text-sm text-gray-500 font-medium mb-1">Total Nilai Estimasi (Kebutuhan Sekolah)</p>
                    <p class="text-3xl font-extrabold text-gray-800">Rp
                        {{ number_format($grandTotalEstimated, 0, ',', '.') }}</p>
                </div>
                <div class="bg-linear-to-r from-indigo-50 to-white p-6 rounded-lg shadow-sm border border-indigo-200">
                    <p class="text-sm text-indigo-500 font-medium mb-1">Total Nilai Resmi (Disepakati CV)</p>
                    <p class="text-3xl font-extrabold text-indigo-700">Rp
                        {{ number_format($grandTotalOfficial, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Dokumen Diterbitkan</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalDocuments }}</p>
                    <p class="text-xs text-gray-400 mt-2">Dokumen resmi yang telah dihasilkan sistem.</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">Aktivitas Pengguna Terakhir</h3>
                    </div>
                    <ul class="divide-y divide-gray-200 max-h-80 overflow-y-auto">
                        @forelse ($recentActivity as $activity)
                            <li class="px-6 py-3 flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $activity->causer?->name ?? 'Sistem' }}
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $activity->description }}</p>
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                    {{ $activity->created_at?->diffForHumans() }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-gray-500">
                                Belum ada aktivitas tercatat.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
