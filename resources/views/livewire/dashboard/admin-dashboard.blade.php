<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Operasional CV') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="filter-date-from" class="block text-xs font-medium text-gray-500 mb-1">Dari
                            Tanggal Pengajuan</label>
                        <input id="filter-date-from" type="date" wire:model.live="dateFrom"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="filter-date-to" class="block text-xs font-medium text-gray-500 mb-1">Sampai
                            Tanggal Pengajuan</label>
                        <input id="filter-date-to" type="date" wire:model.live="dateTo"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" wire:click="resetFilters"
                            class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-500 font-medium">Menunggu Verifikasi Anda</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $pendingVerificationCount }} Dokumen</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-purple-500">
                    <p class="text-sm text-gray-500 font-medium">Negosiasi Menunggu Tanggapan</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $pendingNegotiationCount }} Tanggapan</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 font-medium">Total Proses Aktif Berjalan</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalActiveProcesses }} Pengadaan</p>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-3">Perlu Ditindaklanjuti</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-500 font-medium">Diproses</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $processingCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-500 font-medium">Diterima Sebagian</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $partiallyReceivedCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-500 font-medium">Diterima</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $receivedCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-500 font-medium">Ditagih</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $invoicedCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-500 font-medium">Menunggu Pembayaran</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $paymentPendingCount }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-500 font-medium">Bermasalah</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $disputedCount }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Antrean Verifikasi Masuk</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No. Pengajuan</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Asal Sekolah</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal Masuk</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($pendingVerifications as $request)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-indigo-600">
                                        #{{ substr((string) $request->uuid, 0, 8) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $request->school?->name ?? 'Sekolah Tidak Ditemukan' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->created_at->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="#"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded">Verifikasi
                                            &rarr;</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        Tarik napas! Tidak ada pengajuan yang menunggu verifikasi saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Negosiasi Menunggu Tanggapan CV</h3>
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse ($pendingNegotiations as $negotiation)
                        <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">
                                    #{{ substr((string) ($negotiation->procurementRequest?->uuid ?? ''), 0, 8) }}
                                    <span class="text-sm font-normal text-gray-500">
                                        &middot; {{ $negotiation->procurementRequest?->school?->name ?? 'Sekolah tidak diketahui' }}
                                    </span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Diajukan oleh {{ $negotiation->user?->name ?? 'Pengguna' }} &middot;
                                    Putaran {{ $negotiation->round_number }}
                                </p>
                            </div>
                            <span
                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">
                                Rp {{ number_format((float) $negotiation->offered_price, 0, ',', '.') }}
                            </span>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-gray-500">
                            Tidak ada negosiasi yang menunggu tanggapan CV.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
