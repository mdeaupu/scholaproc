<div>
    <div class="py-1 space-y-6">

            {{-- Sapaan & tanggal --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">Selamat datang,
                        {{ auth()->user()->name }}</h1>
                    <p class="text-sm text-gray-500 mt-1">Anda memiliki akses penuh ke sistem.</p>
                </div>
                <p class="text-sm text-gray-500 flex items-center gap-2 mt-1">
                    <x-mary-icon name="o-calendar-days" class="w-4 h-4 text-[#0046FF]" />
                    {{ \Illuminate\Support\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                </p>
            </div>

            {{-- Filter --}}
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="filter-budget-year" class="block text-xs font-medium text-gray-500 mb-1">Tahun
                            Anggaran</label>
                        <select id="filter-budget-year" wire:model.live="budgetYearId"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#0046FF] focus:ring-[#0046FF]">
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
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#0046FF] focus:ring-[#0046FF]">
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
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#0046FF] focus:ring-[#0046FF]">
                    </div>
                    <div>
                        <label for="filter-date-to" class="block text-xs font-medium text-gray-500 mb-1">Sampai
                            Tanggal</label>
                        <input id="filter-date-to" type="date" wire:model.live="dateTo"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#0046FF] focus:ring-[#0046FF]">
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="button" wire:click="resetFilters"
                        class="text-sm text-[#0046FF] hover:text-[#0046FF]/80 font-semibold">
                        Reset Filter
                    </button>
                </div>
            </div>

            {{-- Kartu statistik --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-blue-50 text-[#0046FF]">
                            <x-mary-icon name="o-building-library" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Sekolah Aktif</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalSchools }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-violet-50 text-violet-500">
                            <x-mary-icon name="o-users" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Pengguna Aktif</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalActiveUsers }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $totalAdminUsers }} admin &middot;
                        {{ $totalSchoolUsers }} sekolah</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-teal-50 text-teal-500">
                            <x-mary-icon name="o-clipboard-document-list" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Total Pengajuan</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalRequests }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-amber-50 text-amber-500">
                            <x-mary-icon name="o-shield-check" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Menunggu Verifikasi</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalSubmittedRequests }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-green-50 text-green-600">
                            <x-mary-icon name="o-check-circle" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Pengadaan Selesai</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalCompletedRequests }}</p>
                </div>
            </div>

            {{-- Status pengadaan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900">Status Pengadaan</h2>
                </div>
                <div class="p-5 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach (\App\Models\ProcurementRequest::STATUS_LABELS as $status => $label)
                        <div
                            class="rounded-lg border border-gray-200 px-4 py-3 flex items-center justify-between {{ \App\Models\ProcurementRequest::STATUS_PILL_CLASSES[$status] }}">
                            <span class="text-xs font-semibold">{{ $label }}</span>
                            <span class="text-xl font-bold">{{ $statusBreakdown[$status] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Nilai transaksi --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-linear-to-r from-gray-50 to-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <p class="text-sm text-gray-500 font-medium mb-1">Total Nilai Estimasi (Kebutuhan Sekolah)</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-800">Rp
                        {{ number_format($grandTotalEstimated, 0, ',', '.') }}</p>
                </div>
                <div class="bg-linear-to-r from-[#0046FF]/5 to-white p-6 rounded-xl shadow-sm border border-[#0046FF]/20">
                    <p class="text-sm text-[#0046FF] font-medium mb-1">Total Nilai Resmi (Disepakati CV)</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0046FF]">Rp
                        {{ number_format($grandTotalOfficial, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Dokumen & aktivitas --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="p-2.5 rounded-full bg-blue-50 text-[#0046FF]">
                            <x-mary-icon name="o-document-text" class="w-6 h-6" />
                        </span>
                        <p class="text-sm font-medium text-gray-500">Dokumen Diterbitkan</p>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 mt-3">{{ $totalDocuments }}</p>
                    <p class="text-xs text-gray-400 mt-2">Dokumen resmi yang telah dihasilkan sistem.</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Aktivitas Pengguna Terakhir</h2>
                    </div>
                    <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                        @forelse ($recentActivity as $activity)
                            <li class="px-5 py-3 flex items-start justify-between gap-4">
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
                            <li class="px-5 py-8 text-center text-gray-500">
                                Belum ada aktivitas tercatat.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
    </div>
</div>
