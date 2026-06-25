<div>
    <x-mary-header title="{{ __('Dasbor Operasional CV') }}" separator />
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-mary-card shadow class="border-l-4 border-[#FF8040]">
                <p class="text-sm text-gray-500 font-medium">Menunggu Verifikasi Anda</p>
                <p class="text-3xl font-bold text-black">{{ $pendingVerifications->count() }} Dokumen</p>
            </x-mary-card>
            <x-mary-card shadow class="border-l-4 border-[#0046FF]">
                <p class="text-sm text-gray-500 font-medium">Total Proses Aktif Berjalan</p>
                <p class="text-3xl font-bold text-black">{{ $totalActiveProcesses }} Pengadaan</p>
            </x-mary-card>
        </div>
        <x-mary-card shadow class="p-0 overflow-hidden border-t-4 border-[#0046FF]">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-black">Antrean Verifikasi Masuk</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No. Pengajuan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Asal Sekolah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Masuk</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($pendingVerifications as $request)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-[#0046FF]">
                                    {{ $request->request_number ?? 'Belum ada nomor' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-black">
                                    {{ $request->school?->name ?? 'Sekolah Tidak Ditemukan' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $request->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#"
                                        class="text-[#0046FF] hover:text-[#0046FF]/80 bg-[#0046FF]/10 px-3 py-1 rounded transition-colors">Verifikasi
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
        </x-mary-card>
    </div>
</div>