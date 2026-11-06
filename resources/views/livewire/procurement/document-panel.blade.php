<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-900">Dokumen Terkait</h2>
            <p class="mt-1 text-sm text-slate-600">
                Berikut adalah dokumen yang tersedia dan dapat diunduh sesuai tahapan pengajuan.
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-mary-icon name="o-information-circle" class="h-4 w-4 shrink-0" />
            <span>Dokumen akan muncul sesuai dengan status transaksi.</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[850px] border-collapse text-left text-sm">
            <thead>
                <tr class="border-y border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600">
                    <th scope="col" class="px-3 py-3">No</th>
                    <th scope="col" class="px-3 py-3">Nama Dokumen</th>
                    <th scope="col" class="px-3 py-3">Deskripsi</th>
                    <th scope="col" class="px-3 py-3">Tipe</th>
                    <th scope="col" class="px-3 py-3">Status</th>
                    <th scope="col" class="px-3 py-3">Tanggal Dibuat</th>
                    <th scope="col" class="px-3 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($documents as $index => $row)
                    <tr wire:key="procurement-document-{{ $row['type'] }}" class="text-slate-700">
                        <td class="whitespace-nowrap px-3 py-3">{{ $index + 1 }}</td>
                        <td class="min-w-48 px-3 py-3 font-medium text-slate-900">{{ $row['name'] }}</td>
                        <td class="min-w-64 px-3 py-3">{{ $row['description'] }}</td>
                        <td class="px-3 py-3">
                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                PDF
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            @if ($row['available'])
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    Tersedia
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                    Belum Tersedia
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            {{ $row['document']?->created_at?->format('d M Y H:i') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            @if ($row['available'])
                                <a href="{{ route('procurement.documents.download', [$procurementRequest, $row['document']]) }}"
                                    class="inline-flex items-center gap-2 rounded-md border border-blue-300 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                    <x-mary-icon name="o-arrow-down-tray" class="h-4 w-4" />
                                    Unduh
                                </a>
                            @else
                                <button type="button" disabled
                                    class="inline-flex cursor-not-allowed items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-xs font-medium text-slate-400">
                                    <x-mary-icon name="o-arrow-down-tray" class="h-4 w-4" />
                                    Belum Tersedia
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
