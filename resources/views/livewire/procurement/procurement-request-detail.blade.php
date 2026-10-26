<div class="space-y-5">
    @php
        $request = $procurementRequest;
        $school = $request->school;
        $schoolAccount = $school?->account;
        $requestNumber =
            'PGN-' . $request->created_at->format('Y') . '-' . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT);
        $estimatedTotal = $request->items->sum(fn($item) => $item->estimatedAmount());
        $isSchool = auth()->user()->isSchool();
        $canEdit =
            $isSchool &&
            in_array(
                $request->status,
                [\App\Models\ProcurementRequest::STATUS_DRAFT, \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION],
                true,
            );
        $statusLabel = $request->status_label;
        $latestRevision = $request->histories
            ->where('status', \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION)
            ->sortByDesc('created_at')
            ->first();
        $latestRejection = $request->histories
            ->where('status', \App\Models\ProcurementRequest::STATUS_REJECTED)
            ->sortByDesc('created_at')
            ->first();
    @endphp

    <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('procurement.index') }}" wire:navigate
            class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline">
            <x-mary-icon name="o-arrow-left" class="h-4 w-4" />
            Kembali ke Daftar Pengajuan
        </a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Detail Pengajuan Barang</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <span class="font-mono font-semibold text-gray-600">{{ $requestNumber }}</span>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                    {{ $school?->name ?? 'Sekolah' }}
                </span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $request->status_pill_class }}">
                <x-mary-icon name="o-information-circle" class="h-4 w-4" />
                {{ $statusLabel }}
            </span>
            <span
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600">
                <x-mary-icon name="o-calendar-days" class="h-4 w-4 text-[#0046FF]" />
                {{ $request->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm') }}
            </span>
        </div>
    </div>

    @if ($request->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION && $latestRevision)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <x-mary-icon name="o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-bold">Revisi diperlukan</p>
                <p class="mt-1">{{ \Illuminate\Support\Str::after($latestRevision->notes, 'Revisi diminta: ') }}</p>
                <p class="mt-2 text-xs text-amber-700">
                    {{ $latestRevision->createdBy?->name ?? 'Admin' }} ·
                    {{ $latestRevision->created_at?->locale('id')->isoFormat('D MMM YYYY HH:mm') }}
                </p>
            </div>
        </div>
    @endif

    @if ($request->status === \App\Models\ProcurementRequest::STATUS_REJECTED)
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
            <x-mary-icon name="o-x-circle" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-bold">Pengajuan ditolak</p>
                <p class="mt-1">{{ $latestRejection?->notes ?? 'Alasan penolakan tidak tersedia.' }}</p>
                @if ($latestRejection)
                    <p class="mt-2 text-xs text-red-700">
                        {{ $latestRejection->createdBy?->name ?? 'Admin' }} ·
                        {{ $latestRejection->created_at?->locale('id')->isoFormat('D MMM YYYY HH:mm') }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <section class="grid grid-cols-2 gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm md:grid-cols-5"
        aria-label="Ringkasan pengajuan">
        <div class="flex items-center gap-3 border-gray-100 p-2 md:border-r">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#0046FF]">
                <x-mary-icon name="o-building-library" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Sekolah</p>
                <p class="truncate text-sm font-semibold text-gray-900">{{ $school?->name ?? '-' }}</p>
                <p class="text-xs text-gray-400">NPSN: {{ $school?->npsn ?? '-' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 border-gray-100 p-2 md:border-r">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-600">
                <x-mary-icon name="o-banknotes" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Sumber Dana</p>
                <p class="truncate text-sm font-semibold text-gray-900">{{ $request->fundingSource?->name ?? '-' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 border-gray-100 p-2 md:border-r">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <x-mary-icon name="o-calendar-days" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Tahun Anggaran</p>
                <p class="truncate text-sm font-semibold text-gray-900">{{ $request->budgetYear?->name ?? '-' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 border-gray-100 p-2 md:border-r">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                <x-mary-icon name="o-tag" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Kategori</p>
                <p class="truncate text-sm font-semibold text-gray-900">{{ $request->packageCategory?->name ?? '-' }}
                </p>
            </div>
        </div>
        <div class="col-span-2 rounded-lg bg-blue-50 p-3 md:col-span-1">
            <p class="text-xs font-medium text-[#0046FF]">Total Estimasi</p>
            <p class="mt-1 text-lg font-extrabold text-gray-900">Rp {{ number_format($estimatedTotal, 0, ',', '.') }}
            </p>
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-12">
        <main class="space-y-5 xl:col-span-8">
            <x-mary-card title="Informasi Pengajuan" separator shadow>
                <div class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm md:grid-cols-2">
                    <div class="space-y-3">
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">Nomor Pengajuan</span>
                            <span class="font-mono font-medium text-gray-800">{{ $requestNumber }}</span>
                        </div>
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">Sekolah</span>
                            <span class="font-medium text-gray-800">{{ $school?->name ?? '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">NPSN</span>
                            <span class="text-gray-800">{{ $school?->npsn ?? '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">Alamat Sekolah</span>
                            <span class="text-gray-800">{{ $school?->address ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">Nama Pengaju</span>
                            <span class="font-medium text-gray-800">
                                {{ $schoolAccount?->name ?? ($school?->principal_name ?? '-') }}
                            </span>
                        </div>
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">Email</span>
                            <span
                                class="break-all text-gray-800">{{ $schoolAccount?->email ?? ($school?->email ?? '-') }}</span>
                        </div>
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">No. Telepon</span>
                            <span class="text-gray-800">{{ $school?->phone_number ?? '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[130px_1fr] gap-2">
                            <span class="text-gray-500">Tanggal Pengajuan</span>
                            <span
                                class="text-gray-800">{{ $request->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm') }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-5">
                    <div class="mb-3">
                        <h2 class="font-bold text-gray-900">Detail Barang</h2>
                        <p class="text-sm text-gray-500">Rincian barang dan estimasi yang diajukan sekolah.</p>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                                <tr>
                                    <th class="whitespace-nowrap px-3 py-3">No.</th>
                                    <th class="whitespace-nowrap px-3 py-3">Nama Barang</th>
                                    <th class="px-3 py-3">Spesifikasi</th>
                                    <th class="whitespace-nowrap px-3 py-3">Satuan</th>
                                    <th class="whitespace-nowrap px-3 py-3 text-right">Jumlah</th>
                                    <th class="whitespace-nowrap px-3 py-3 text-right">Estimasi Harga</th>
                                    <th class="whitespace-nowrap px-3 py-3 text-right">Total Estimasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($request->items as $index => $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-3 text-gray-400">{{ $index + 1 }}</td>
                                        <td class="px-3 py-3 font-medium text-gray-900">{{ $item->item_name }}</td>
                                        <td class="px-3 py-3 text-gray-600">{{ $item->specification ?: '-' }}</td>
                                        <td class="px-3 py-3 text-gray-600">{{ $item->unit?->name ?? '-' }}</td>
                                        <td class="px-3 py-3 text-right text-gray-700">
                                            {{ number_format($item->quantity, 0, ',', '.') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right text-gray-700">
                                            Rp {{ number_format((float) $item->estimated_price, 0, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-gray-900">
                                            Rp {{ number_format($item->estimatedAmount(), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                                            Belum ada barang pada pengajuan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-blue-50/60">
                                <tr>
                                    <td colspan="6" class="px-3 py-3 text-right font-semibold text-gray-700">Total
                                        Estimasi</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right font-extrabold text-[#0046FF]">
                                        Rp {{ number_format($estimatedTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">
                        Nilai di atas merupakan estimasi dari sekolah, bukan penawaran atau nilai final.
                    </p>
                </div>
            </x-mary-card>
        </main>

        <aside class="space-y-5 xl:col-span-4">
            <x-mary-card title="Riwayat Status" subtitle="Catatan perubahan status pengajuan." separator shadow>
                @if ($request->histories->isEmpty())
                    <div class="flex gap-3 py-2">
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#0046FF]">
                            <x-mary-icon name="o-document-plus" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Pengajuan dibuat</p>
                            <p class="text-xs text-gray-500">
                                {{ $request->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm') }}</p>
                            <p class="mt-1 text-xs text-gray-500">Status saat ini: {{ $statusLabel }}</p>
                        </div>
                    </div>
                @else
                    <ol class="relative space-y-0">
                        @foreach ($request->histories->sortBy('created_at') as $index => $history)
                            <li class="relative flex gap-3 pb-5 last:pb-0">
                                @if (!$loop->last)
                                    <span class="absolute left-3.5 top-8 h-full border-l border-gray-200"></span>
                                @endif
                                <span
                                    class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-white text-[#0046FF]">
                                    <x-mary-icon :name="$history->status === \App\Models\ProcurementRequest::STATUS_REJECTED
                                        ? 'o-x-circle'
                                        : ($history->status === \App\Models\ProcurementRequest::STATUS_COMPLETED
                                            ? 'o-check-circle'
                                            : 'o-arrow-path')" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ \App\Models\ProcurementRequest::STATUS_LABELS[$history->status] ?? \Illuminate\Support\Str::headline($history->status) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $history->created_at?->locale('id')->isoFormat('D MMM YYYY HH:mm') ?? '-' }}
                                        @if ($history->createdBy)
                                            · {{ $history->createdBy->name }}
                                        @endif
                                    </p>
                                    @if ($history->notes)
                                        <p class="mt-1 whitespace-pre-line text-xs leading-relaxed text-gray-600">
                                            {{ $history->notes }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-mary-card>

            @if ($isSchool)
                <x-mary-card title="Aksi Pengajuan" separator shadow>
                    <div class="space-y-3">
                        @if ($canEdit)
                            <x-mary-button :label="$request->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION
                                ? 'Perbaiki Pengajuan'
                                : 'Edit Pengajuan'" icon="o-pencil-square"
                                link="{{ route('procurement.edit', $request->id) }}" wire:navigate
                                class="w-full border-[#0046FF] bg-white text-[#0046FF] hover:bg-blue-50" />
                        @endif

                        @if ($request->status === \App\Models\ProcurementRequest::STATUS_DRAFT)
                            <x-mary-button label="Kirim Pengajuan" icon="o-paper-airplane" wire:click="submitRequest"
                                wire:confirm="Kirim pengajuan ini untuk diverifikasi?" wire:loading.attr="disabled"
                                spinner="submitRequest" class="w-full bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
                            <x-mary-button label="Hapus Pengajuan" icon="o-trash" wire:click="confirmDeleteDraft"
                                class="w-full border border-red-200 bg-white text-red-600 hover:bg-red-50" />
                        @endif

                        @if (! $canEdit && $request->status !== \App\Models\ProcurementRequest::STATUS_DRAFT)
                            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500">
                                Tidak ada tindakan yang tersedia untuk status pengajuan saat ini.
                            </p>
                        @endif
                    </div>
                </x-mary-card>
            @endif

            @if (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <livewire:procurement.procurement-review-panel :procurement-request="$request" />
            @endif
        </aside>
    </div>

    <x-mary-modal wire:model="confirmingDeleteDraft" title="Hapus Pengajuan" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="shrink-0 rounded-full bg-[#FF8040]/10 p-3 text-[#FF8040]">
                <x-mary-icon name="o-trash" class="h-7 w-7" />
            </div>
            <div>
                <p class="font-semibold text-black">
                    Hapus <span class="text-[#FF8040]">"{{ $requestNumber }}"</span>?
                </p>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    Draf pengajuan dan rincian barangnya akan dihapus dari tampilan, tetapi tetap disimpan sebagai
                    riwayat dan tidak dapat dipulihkan langsung.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDeleteDraft', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="deleteDraft" wire:loading.attr="disabled"
                spinner="deleteDraft" class="border-none bg-[#FF8040] text-white" />
        </x-slot:actions>
    </x-mary-modal>
</div>
