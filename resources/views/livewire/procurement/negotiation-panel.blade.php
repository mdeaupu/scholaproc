@php
    $user = auth()->user();
    $isSchool = $user->isSchool();
    $isProvider = $user->isAdmin() || $user->isSuperAdmin();
    $canDecide = $isSchool && $user->canDecideNegotiation($procurementRequest);
    $progress = $procurementRequest->negotiationProgress();
    $nextOfferBy = $selectedItem?->nextOfferBy();
    $canSubmitOffer = ($isSchool && $nextOfferBy === \App\Models\ProcurementNegotiation::OFFERED_BY_SCHOOL)
        || ($isProvider && $nextOfferBy === \App\Models\ProcurementNegotiation::OFFERED_BY_ADMIN);
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Negosiasi Harga Per Item</h3>
                <p class="text-sm text-slate-500">Riwayat dan persetujuan harga setiap barang.</p>
            </div>
            <x-mary-badge
                :value="$progress['percentage'] === 100 ? 'Selesai' : $progress['settled'] . '/' . $progress['total'] . ' Item'"
                class="{{ $progress['percentage'] === 100 ? 'bg-emerald-500 text-white' : 'bg-[#0046FF] text-white' }} badge-lg font-semibold" />
        </div>

        <div class="mb-2 h-2.5 w-full rounded-full bg-gray-200">
            <div class="h-2.5 rounded-full bg-[#0046FF] transition-all duration-500"
                style="width: {{ $progress['percentage'] }}%"></div>
        </div>
        <div class="flex justify-between text-[11px] text-slate-500">
            <span>{{ $progress['accepted'] }} diterima</span>
            <span>{{ $progress['rejected'] }} ditolak</span>
            <span>{{ $progress['pending'] }} menunggu</span>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto border-b border-gray-100 p-4">
            <div class="flex min-w-max gap-2">
                @foreach ($items as $item)
                    @php
                        $isActive = $selectedItemId == $item->id;
                        $statusBadge = match ($item->negotiation_status) {
                            'accepted' => 'border-emerald-200 bg-emerald-100 text-emerald-700',
                            'rejected' => 'border-red-200 bg-red-100 text-red-700',
                            'negotiating' => 'border-blue-200 bg-blue-50 text-[#0046FF]',
                            default => 'border-gray-200 bg-gray-100 text-gray-500',
                        };
                        $statusLabel = match ($item->negotiation_status) {
                            'accepted' => 'Diterima',
                            'rejected' => 'Ditolak',
                            'negotiating' => 'Nego',
                            default => 'Mulai',
                        };
                    @endphp
                    <button type="button" wire:click="selectItem({{ $item->id }})"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium whitespace-nowrap transition
                            {{ $isActive ? 'border-[#0046FF] bg-blue-50 text-[#0046FF] shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-300' }}">
                        <span class="max-w-[160px] truncate">{{ $item->item_name }}</span>
                        <span class="rounded border px-1.5 py-0.5 text-[10px] font-bold {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($selectedItem)
            <div class="space-y-5 p-4 md:p-5">
                <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <span class="block text-xs text-slate-500">Nama Barang</span>
                            <span class="font-semibold text-slate-900">{{ $selectedItem->item_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Spesifikasi</span>
                            <span class="text-xs font-medium text-slate-700">{{ $selectedItem->specification ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Jumlah</span>
                            <span class="font-medium text-slate-900">
                                {{ $selectedItem->quantity }} {{ $selectedItem->unit?->name ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500">Harga Estimasi</span>
                            <span class="font-semibold text-slate-900">
                                Rp {{ number_format($selectedItem->estimated_price, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @if ($selectedItem->official_price)
                        <div class="mt-3 flex items-center gap-2 border-t border-gray-200 pt-3">
                            <span class="text-xs text-slate-500">Harga Resmi (Final):</span>
                            <span class="text-sm font-bold text-emerald-600">
                                Rp {{ number_format($selectedItem->official_price, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                    <section class="rounded-xl border border-gray-200 bg-white p-4 md:col-span-2 md:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h4 class="font-bold text-slate-900">Riwayat Negosiasi</h4>
                            <span class="text-xs text-slate-500">{{ $negotiations->count() }} putaran</span>
                        </div>

                        @if ($negotiations->isEmpty())
                            <div class="rounded-lg border border-dashed border-gray-300 bg-slate-50 px-4 py-10 text-center">
                                <svg class="mx-auto mb-2 h-9 w-9 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-sm font-medium text-slate-700">Belum ada tawaran harga</p>
                                <p class="mt-1 text-xs text-slate-500">Riwayat tawaran akan tampil di sini.</p>
                            </div>
                        @else
                            <div class="relative space-y-5 before:absolute before:bottom-4 before:left-4 before:top-4 before:w-px before:bg-blue-100 md:before:left-5">
                                @foreach ($negotiations as $nego)
                                    @php
                                        $isLatest = $nego->isLatestRound();
                                        $isPending = $nego->status === \App\Models\ProcurementNegotiation::STATUS_PENDING;
                                        $isAccepted = $nego->status === \App\Models\ProcurementNegotiation::STATUS_ACCEPTED;
                                        $isRejected = $nego->status === \App\Models\ProcurementNegotiation::STATUS_REJECTED;
                                        $isFromAdmin = $nego->isFromSupplierRepresentative();
                                        $isFromSchoolUser = $nego->isFromSchool();
                                        $senderLabel = $isFromAdmin ? 'Admin' : 'Sekolah';
                                        $markerClass = $isFromAdmin
                                            ? 'bg-indigo-100 text-indigo-700 ring-indigo-50'
                                            : 'bg-blue-100 text-[#0046FF] ring-blue-50';
                                        $bubbleClass = $isFromAdmin
                                            ? 'border-indigo-100 bg-indigo-50'
                                            : 'border-blue-100 bg-blue-50';
                                        $badgeClass = match (true) {
                                            $isAccepted => 'bg-emerald-100 text-emerald-700',
                                            $isRejected => 'bg-red-100 text-red-700',
                                            $isPending && $isFromAdmin => 'bg-amber-100 text-amber-800',
                                            $isPending && $isFromSchoolUser => 'bg-blue-100 text-blue-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                        $badgeLabel = match (true) {
                                            $isAccepted => 'Diterima',
                                            $isRejected => 'Ditolak',
                                            $isPending && $isFromAdmin => 'Menunggu Sekolah',
                                            $isPending && $isFromSchoolUser => 'Menunggu Admin',
                                            default => 'Ditanggapi',
                                        };
                                    @endphp

                                    <article class="relative flex gap-3 {{ $isFromSchoolUser ? 'md:flex-row-reverse' : '' }}">
                                        <div class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-4 {{ $markerClass }}">
                                            @if ($isFromAdmin)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6v12m6-6H6" />
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 10h18M5 10V7l7-4 7 4v3M5 10v10m14-10v10M3 20h18M9 14h.01M15 14h.01" />
                                                </svg>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1 {{ $isFromSchoolUser ? 'md:text-right' : '' }}">
                                            <div class="mb-1 flex flex-wrap items-center gap-2 {{ $isFromSchoolUser ? 'md:justify-end' : '' }}">
                                                <span class="text-xs font-bold text-slate-800">{{ $senderLabel }}</span>
                                                @if ($nego->user)
                                                    <span class="text-xs text-slate-500">{{ $nego->user->name }}</span>
                                                @endif
                                                <time class="text-[11px] text-slate-400">
                                                    {{ \Carbon\Carbon::parse($nego->created_at)->format('d M Y H:i') }} WIB
                                                </time>
                                            </div>

                                            <div class="inline-block max-w-full rounded-xl border p-3 text-left {{ $bubbleClass }}">
                                                <div class="mb-1 flex flex-wrap items-center gap-2">
                                                    <span class="text-sm font-bold text-slate-900">
                                                        Rp {{ number_format($nego->offered_price, 0, ',', '.') }}
                                                    </span>
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">
                                                        {{ $badgeLabel }}
                                                    </span>
                                                </div>
                                                @if ($nego->notes)
                                                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $nego->notes }}</p>
                                                @else
                                                    <p class="text-sm italic text-slate-500">Tidak ada catatan penawaran.</p>
                                                @endif
                                                @if ($isRejected && $nego->response_note)
                                                    <div class="mt-2 rounded-md border border-red-100 bg-red-50 p-2">
                                                        <span class="block text-[11px] font-semibold text-red-700">Alasan penolakan</span>
                                                        <p class="whitespace-pre-line text-sm text-red-800">{{ $nego->response_note }}</p>
                                                    </div>
                                                @endif

                                                <div class="mt-2 flex items-center justify-between gap-3 border-t border-black/5 pt-2">
                                                    <span class="text-[11px] text-slate-500">Putaran {{ $nego->round_number }}</span>
                                                    <button type="button" wire:click="showDetail({{ $nego->id }})"
                                                        class="text-[11px] font-medium text-[#0046FF] hover:underline">
                                                        Detail
                                                    </button>
                                                </div>
                                            </div>

                                            @if ($isPending && $isLatest && $isFromAdmin && $canDecide)
                                                <div class="mt-2 flex gap-2 {{ $isFromSchoolUser ? 'md:justify-end' : '' }}">
                                                    <x-mary-button label="Terima" icon="o-check"
                                                        wire:click="accept({{ $nego->id }})"
                                                        wire:loading.attr="disabled" spinner="accept"
                                                        class="btn-xs border-none bg-emerald-500 text-white hover:bg-emerald-600" />
                                                    <x-mary-button label="Tolak" icon="o-x-mark"
                                                        wire:click="openRejectModal({{ $nego->id }})"
                                                        class="btn-xs border-none bg-red-500 text-white hover:bg-red-600" />
                                                </div>
                                            @elseif ($isPending && $isLatest && $isFromSchoolUser && $isProvider)
                                                <div class="mt-2 flex gap-2">
                                                    <x-mary-button label="Terima Penawaran Sekolah" icon="o-check"
                                                        wire:click="accept({{ $nego->id }})"
                                                        wire:loading.attr="disabled" spinner="accept"
                                                        class="btn-xs border-none bg-emerald-500 text-white hover:bg-emerald-600" />
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
                        @if (!$selectedItem->isNegotiationSettled())
                            @if ($procurementRequest->status !== \App\Models\ProcurementRequest::STATUS_NEGOTIATION
                                || !$procurementRequest->hasSubsidiary())
                                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                    Penawaran tersedia setelah pengajuan diteruskan dan Anak Perusahaan ditetapkan.
                                </div>
                            @elseif ($canSubmitOffer)
                                <h4 class="font-bold text-slate-900">
                                    {{ $isSchool ? 'Ajukan Penawaran Balik' : 'Ajukan Penawaran' }}
                                </h4>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    {{ $isSchool
                                        ? 'Admin telah mengajukan harga. Masukkan penawaran balik jika diperlukan.'
                                        : ($nextOfferBy === \App\Models\ProcurementNegotiation::OFFERED_BY_ADMIN && $negotiations->isEmpty()
                                            ? 'Ajukan harga penawaran pertama untuk sekolah.'
                                            : 'Sekolah telah mengajukan tawaran balik. Masukkan penawaran harga berikutnya jika diperlukan.') }}
                                </p>
                                <div class="mt-4 space-y-4">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-slate-700">
                                            Harga Penawaran <span class="text-red-500">*</span>
                                        </span>
                                        <span class="flex overflow-hidden rounded-lg border border-gray-300 focus-within:border-[#0046FF] focus-within:ring-2 focus-within:ring-blue-100">
                                            <span class="border-r border-gray-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600">Rp</span>
                                            <input type="number" min="1" step="1" wire:model="offerPrice"
                                                placeholder="Masukkan harga" required
                                                class="min-w-0 flex-1 border-0 px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-0">
                                        </span>
                                        @error('offerPrice')
                                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-slate-700">Catatan (Opsional)</span>
                                        <textarea wire:model="offerNotes" rows="4" maxlength="500"
                                            placeholder="Tambahkan catatan..."
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#0046FF] focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
                                        <span class="mt-1 block text-right text-[11px] text-slate-400">Maksimal 500 karakter</span>
                                    </label>

                                    <button type="button" wire:click="offer" wire:loading.attr="disabled"
                                        wire:target="offer"
                                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#0046FF] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        <svg wire:loading.remove wire:target="offer" class="h-4 w-4" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        <svg wire:loading wire:target="offer" class="h-4 w-4 animate-spin" fill="none"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        <span wire:loading.remove wire:target="offer">Kirim Penawaran</span>
                                        <span wire:loading wire:target="offer">Mengirim...</span>
                                    </button>
                                </div>
                            @else
                                <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
                                    @if ($nextOfferBy === \App\Models\ProcurementNegotiation::OFFERED_BY_ADMIN)
                                        Menunggu Admin/Superadmin mengajukan penawaran atau penawaran berikutnya.
                                    @elseif ($nextOfferBy === \App\Models\ProcurementNegotiation::OFFERED_BY_SCHOOL)
                                        Menunggu pihak sekolah mengajukan penawaran balik.
                                    @else
                                        Tidak ada penawaran lanjutan untuk item ini.
                                    @endif
                                </div>
                            @endif
                        @else
                            @php
                                $settledNego = $negotiations->where('status', \App\Models\ProcurementNegotiation::STATUS_ACCEPTED)->last();
                            @endphp
                            @if ($settledNego)
                                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-center">
                                    <p class="text-sm font-semibold text-emerald-700">Harga telah disepakati</p>
                                    <p class="mt-1 text-lg font-bold text-emerald-600">
                                        Rp {{ number_format($selectedItem->official_price, 0, ',', '.') }}
                                    </p>
                                </div>
                            @else
                                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-center text-sm font-semibold text-red-700">
                                    Negosiasi untuk item ini telah ditolak.
                                </div>
                            @endif
                        @endif
                    </section>
                </div>

                <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
                    <div class="mb-4">
                        <h4 class="font-bold text-slate-900">Detail Penawaran</h4>
                        <p class="mt-1 text-xs text-slate-500">Rekap seluruh putaran penawaran untuk barang ini.</p>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full min-w-[760px] divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th scope="col" class="px-3 py-3 font-semibold">No</th>
                                    <th scope="col" class="px-3 py-3 font-semibold">Tanggal</th>
                                    <th scope="col" class="px-3 py-3 font-semibold">Pengirim</th>
                                    <th scope="col" class="px-3 py-3 font-semibold">Harga Penawaran</th>
                                    <th scope="col" class="px-3 py-3 font-semibold">Catatan</th>
                                    <th scope="col" class="px-3 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white text-slate-700">
                                @forelse ($negotiations as $index => $nego)
                                    @php
                                        $tableStatus = match ($nego->status) {
                                            \App\Models\ProcurementNegotiation::STATUS_ACCEPTED => ['Diterima', 'bg-emerald-100 text-emerald-700'],
                                            \App\Models\ProcurementNegotiation::STATUS_REJECTED => ['Ditolak', 'bg-red-100 text-red-700'],
                                            \App\Models\ProcurementNegotiation::STATUS_PENDING => ['Menunggu', 'bg-amber-100 text-amber-800'],
                                            \App\Models\ProcurementNegotiation::STATUS_COUNTERED => ['Dibalas', 'bg-slate-100 text-slate-600'],
                                            default => [\Illuminate\Support\Str::headline($nego->status), 'bg-gray-100 text-gray-600'],
                                        };
                                    @endphp
                                    <tr class="align-top hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-3 py-3">{{ $index + 1 }}</td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            {{ \Carbon\Carbon::parse($nego->created_at)->format('d M Y H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            {{ $nego->isFromSupplierRepresentative() ? 'Admin' : 'Sekolah' }}
                                            @if ($nego->user)
                                                <span class="block text-[11px] text-slate-400">{{ $nego->user->name }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 font-medium">
                                            Rp {{ number_format($nego->offered_price, 0, ',', '.') }}
                                        </td>
                                        <td class="max-w-xs whitespace-pre-line px-3 py-3">
                                            {{ $nego->notes ?: '-' }}
                                            @if ($nego->status === \App\Models\ProcurementNegotiation::STATUS_REJECTED && $nego->response_note)
                                                <span class="mt-1 block text-[11px] text-red-700">
                                                    Alasan penolakan: {{ $nego->response_note }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $tableStatus[1] }}">
                                                {{ $tableStatus[0] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                            Belum ada detail penawaran.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif
    </section>

    @if ($canDecide)
        <x-mary-modal wire:model="showRejectModal" title="Tolak Tawaran Harga" class="backdrop-blur"
            title-class="text-red-600">
            <x-mary-form wire:submit="confirmReject">
                <x-mary-textarea label="Alasan Penolakan" wire:model="rejectReason"
                    placeholder="Masukkan alasan penolakan tawaran harga..." required />
                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.showRejectModal=false" class="btn-ghost text-black" />
                    <x-mary-button label="Tolak Tawaran" type="submit" spinner="confirmReject"
                        class="border-none bg-red-500 text-white" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    @endif

    <x-mary-modal wire:model="showDetailModal" title="Detail Tawaran" class="backdrop-blur"
        title-class="text-[#0046FF]">
        @if ($detailNegotiation)
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-slate-500">Putaran</span>
                    <span class="font-semibold text-slate-900">{{ $detailNegotiation->round_number }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-slate-500">Diajukan Oleh</span>
                    <span class="font-semibold text-slate-900">
                        {{ $detailNegotiation->isFromSupplierRepresentative() ? 'Admin' : 'Sekolah' }}
                    </span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-slate-500">User</span>
                    <span class="font-medium text-slate-700">{{ $detailNegotiation->user?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-slate-500">Harga Ditawarkan</span>
                    <span class="text-lg font-bold text-[#0046FF]">
                        Rp {{ number_format($detailNegotiation->offered_price, 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-slate-500">Status</span>
                    @php
                        $statusBadgeClass = match ($detailNegotiation->status) {
                            'accepted' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            'pending' => 'bg-blue-100 text-blue-700',
                            'countered' => 'bg-gray-100 text-gray-600',
                            default => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $statusBadgeClass }}">
                        {{ \Illuminate\Support\Str::headline($detailNegotiation->status) }}
                    </span>
                </div>
                @if ($detailNegotiation->notes)
                    <div class="rounded-lg border border-gray-100 bg-slate-50 p-3">
                        <span class="mb-1 block text-xs text-slate-500">Catatan penawaran:</span>
                        <p class="whitespace-pre-line text-slate-700">{{ $detailNegotiation->notes }}</p>
                    </div>
                @endif
                @if ($detailNegotiation->response_note)
                    <div class="rounded-lg border border-red-100 bg-red-50 p-3">
                        <span class="mb-1 block text-xs text-red-600">Alasan penolakan:</span>
                        <p class="whitespace-pre-line text-slate-700">{{ $detailNegotiation->response_note }}</p>
                    </div>
                @endif
                <div class="text-right text-[11px] text-slate-400">
                    Diajukan pada {{ \Carbon\Carbon::parse($detailNegotiation->created_at)->format('d M Y H:i') }} WIB
                </div>
            </div>
        @endif
        <x-slot:actions>
            <x-mary-button label="Tutup" @click="$wire.showDetailModal=false" class="btn-ghost text-black" />
        </x-slot:actions>
    </x-mary-modal>
</div>
