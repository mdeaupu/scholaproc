@php
    $user = auth()->user();
    $isSchool = $user->isSchool();
    $canDecide = $isSchool && $user->canDecideNegotiation($procurementRequest);
    $progress = $procurementRequest->negotiationProgress();
@endphp

<div class="space-y-6">
    {{-- ─── Header & Progress ─────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-bold text-lg text-black">Negosiasi Harga Per Item</h3>
                <p class="text-sm text-gray-500">Tawar-menawar harga antara pihak sekolah dan CV/Supplier.</p>
            </div>
            <x-mary-badge
                :value="$progress['percentage'] === 100 ? 'Selesai' : $progress['settled'] . '/' . $progress['total'] . ' Item'"
                class="{{ $progress['percentage'] === 100 ? 'bg-emerald-500 text-white' : 'bg-[#0046FF] text-white' }} badge-lg font-semibold" />
        </div>

        {{-- Progress Bar --}}
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
            <div class="bg-[#0046FF] h-2.5 rounded-full transition-all duration-500"
                style="width: {{ $progress['percentage'] }}%"></div>
        </div>
        <div class="flex justify-between text-[11px] text-gray-500">
            <span>{{ $progress['accepted'] }} diterima</span>
            <span>{{ $progress['rejected'] }} ditolak</span>
            <span>{{ $progress['pending'] }} menunggu</span>
        </div>
    </div>

    {{-- ─── Item Tabs ─────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="p-4 border-b border-gray-100 overflow-x-auto">
            <div class="flex gap-2 min-w-max">
                @foreach ($items as $item)
                    @php
                        $isActive = $selectedItemId == $item->id;
                        $statusBadge = match($item->negotiation_status) {
                            'accepted' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'rejected' => 'bg-red-100 text-red-700 border-red-200',
                            'negotiating' => 'bg-[#0046FF]/10 text-[#0046FF] border-[#0046FF]/20',
                            default => 'bg-gray-100 text-gray-500 border-gray-200',
                        };
                        $statusLabel = match($item->negotiation_status) {
                            'accepted' => 'Diterima',
                            'rejected' => 'Ditolak',
                            'negotiating' => 'Nego',
                            default => 'Mulai',
                        };
                    @endphp
                    <button wire:click="selectItem({{ $item->id }})
                        class="flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-medium transition-all whitespace-nowrap
                        {{ $isActive ? 'border-[#0046FF] bg-[#0046FF]/5 text-[#0046FF] shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-[#0046FF]/50' }}">
                        <span class="truncate max-w-[120px]">{{ $item->item_name }}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($selectedItem)
            <div class="p-5">
                {{-- Item Info --}}
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-5">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-gray-500 block">Nama Barang</span>
                            <span class="font-semibold text-black">{{ $selectedItem->item_name }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Spesifikasi</span>
                            <span class="font-medium text-gray-700 text-xs">{{ $selectedItem->specification ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Jumlah</span>
                            <span class="font-medium text-black">{{ $selectedItem->quantity }} {{ $selectedItem->unit?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Harga Estimasi</span>
                            <span class="font-semibold text-black">Rp {{ number_format($selectedItem->estimated_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @if ($selectedItem->official_price)
                        <div class="mt-3 pt-3 border-t border-gray-200 flex items-center gap-2">
                            <span class="text-xs text-gray-500">Harga Resmi (Final):</span>
                            <span class="text-sm font-bold text-emerald-600">Rp {{ number_format($selectedItem->official_price, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Negotiation History --}}
                <div class="mb-5">
                    <h4 class="font-bold text-sm text-black mb-3">Riwayat Negosiasi</h4>
                    @if ($negotiations->isEmpty())
                        <div class="text-center py-6 text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <p class="text-sm font-medium">Belum ada tawaran harga</p>
                            <p class="text-xs mt-1">Mulai negosiasi dengan mengajukan harga di bawah.</p>
                        </div>
                    @else
                        <div class="space-y-3 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-200">
                            @foreach ($negotiations as $nego)
                                @php
                                    $isLatest = $nego->isLatestRound();
                                    $isPending = $nego->status === \App\Models\ProcurementNegotiation::STATUS_PENDING;
                                    $isAccepted = $nego->status === \App\Models\ProcurementNegotiation::STATUS_ACCEPTED;
                                    $isRejected = $nego->status === \App\Models\ProcurementNegotiation::STATUS_REJECTED;
                                    $isFromAdmin = $nego->isFromSupplierRepresentative();
                                    $isFromSchoolUser = $nego->isFromSchool();

                                    $dotColor = match(true) {
                                        $isAccepted => 'bg-emerald-500',
                                        $isRejected => 'bg-red-500',
                                        $isFromAdmin => 'bg-[#FF8040]',
                                        default => 'bg-[#0046FF]',
                                    };

                                    $badgeColor = match(true) {
                                        $isAccepted => 'bg-emerald-100 text-emerald-700',
                                        $isRejected => 'bg-red-100 text-red-700',
                                        $isPending && $isFromAdmin => 'bg-[#FF8040]/10 text-[#FF8040]',
                                        $isPending && $isFromSchoolUser => 'bg-[#0046FF]/10 text-[#0046FF]',
                                        default => 'bg-gray-100 text-gray-500',
                                    };

                                    $badgeLabel = match(true) {
                                        $isAccepted => 'Diterima',
                                        $isRejected => 'Ditolak',
                                        $isPending && $isFromAdmin => 'Menunggu Sekolah',
                                        $isPending && $isFromSchoolUser => 'Menunggu CV',
                                        default => 'Selesai',
                                    };
                                @endphp
                                <div class="flex gap-3 relative pb-4 last:pb-0">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center z-10 {{ $dotColor }} flex-shrink-0 mt-0.5">
                                        <span class="text-[10px] font-bold text-white">{{ $nego->round_number }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs font-bold text-black">
                                                    Rp {{ number_format($nego->offered_price, 0, ',', '.') }}
                                                </span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $badgeColor }}">
                                                    {{ $badgeLabel }}
                                                </span>
                                            </div>
                                            <button wire:click="showDetail({{ $nego->id }})"
                                                class="text-[10px] text-gray-400 hover:text-[#0046FF] underline">Detail</button>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span class="text-[11px] text-gray-500">
                                                Putaran {{ $nego->round_number }} &middot;
                                                {{ $isFromAdmin ? 'CV/Supplier' : 'Sekolah' }}
                                            </span>
                                            @if ($nego->user)
                                                <span class="text-[11px] text-gray-400">&middot; {{ $nego->user->name }}</span>
                                            @endif
                                        </div>
                                        @if ($nego->notes)
                                            <p class="text-[11px] text-gray-400 mt-1 italic">&quot;{{ $nego->notes }}&quot;</p>
                                        @endif
                                        <p class="text-[10px] text-gray-400 mt-1">
                                            {{ \Carbon\Carbon::parse($nego->created_at)->format('d M Y H:i') }} WIB
                                        </p>

                                        {{-- Accept / Reject buttons for school on latest pending round --}}
                                        @if ($isPending && $isLatest && $isFromAdmin && $canDecide)
                                            <div class="flex gap-2 mt-2">
                                                <x-mary-button label="Terima" icon="o-check"
                                                    wire:click="accept({{ $nego->id }})"
                                                    wire:loading.attr="disabled" spinner="accept"
                                                    class="btn-xs bg-emerald-500 text-white border-none hover:bg-emerald-600" />
                                                <x-mary-button label="Tolak" icon="o-x-mark"
                                                    wire:click="openRejectModal({{ $nego->id }})"
                                                    class="btn-xs bg-[#FF8040] text-white border-none hover:bg-[#FF8040]/90" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Offer Form --}}
                @if (!$selectedItem->isNegotiationSettled())
                    @if ($selectedItem->negotiation_status === 'not_started' && !$procurementRequest->canStartNegotiation())
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                            <p class="text-sm text-gray-500">Negosiasi dapat dimulai setelah supplier ditentukan.</p>
                        </div>
                    @else
                        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50">
                            <h4 class="font-bold text-sm text-black mb-3">
                                {{ $isSchool ? 'Ajukan Tawaran Balik' : 'Ajukan Harga Penawaran' }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-1">
                                    <x-mary-input label="Harga Penawaran (Rp)" type="number"
                                        wire:model="offerPrice" placeholder="0" prefix="Rp" required />
                                </div>
                                <div class="md:col-span-2">
                                    <x-mary-input label="Catatan (Opsional)" wire:model="offerNotes"
                                        placeholder="Alasan tawaran harga..." />
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <x-mary-button label="Kirim Tawaran" icon="o-paper-airplane"
                                    wire:click="offer" wire:loading.attr="disabled" spinner="offer"
                                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none btn-sm" />
                            </div>
                        </div>
                    @endif
                @else
                    @php
                        $settledNego = $negotiations->where('status', 'accepted')->last();
                    @endphp
                    @if ($settledNego)
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                            <svg class="w-8 h-8 mx-auto mb-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-semibold text-emerald-700">Harga telah disepakati</p>
                            <p class="text-lg font-bold text-emerald-600 mt-1">Rp {{ number_format($selectedItem->official_price, 0, ',', '.') }}</p>
                        </div>
                    @else
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                            <svg class="w-8 h-8 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l2-2m-2 2l-2-2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-semibold text-red-600">Tawaran ditolak oleh sekolah</p>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>

    {{-- ─── Reject Modal ──────────────────────────────────────────── --}}
    <x-mary-modal wire:model="showRejectModal" title="Tolak Tawaran Harga" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <x-mary-form wire:submit="confirmReject">
            <x-mary-textarea label="Alasan Penolakan" wire:model="rejectReason"
                placeholder="Masukkan alasan penolakan tawaran harga..." required />
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.showRejectModal=false" class="btn-ghost text-black" />
                <x-mary-button label="Tolak Tawaran" type="submit" spinner="confirmReject"
                    class="bg-[#FF8040] text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- ─── Detail Modal ──────────────────────────────────────────── --}}
    <x-mary-modal wire:model="showDetailModal" title="Detail Tawaran" class="backdrop-blur"
        title-class="text-[#0046FF]">
        @if ($detailNegotiation)
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Putaran</span>
                    <span class="font-semibold text-black">{{ $detailNegotiation->round_number }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Diajukan Oleh</span>
                    <span class="font-semibold text-black">
                        {{ $detailNegotiation->isFromSupplierRepresentative() ? 'CV/Supplier' : 'Sekolah' }}
                    </span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">User</span>
                    <span class="font-medium text-gray-700">{{ $detailNegotiation->user?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Harga Ditawarkan</span>
                    <span class="font-bold text-[#0046FF] text-lg">
                        Rp {{ number_format($detailNegotiation->offered_price, 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Status</span>
                    @php
                        $statusBadgeClass = match($detailNegotiation->status) {
                            'accepted' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            'pending' => 'bg-[#0046FF]/10 text-[#0046FF]',
                            'countered' => 'bg-gray-100 text-gray-600',
                            default => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $statusBadgeClass }}">
                        {{ Str::headline($detailNegotiation->status) }}
                    </span>
                </div>
                @if ($detailNegotiation->notes)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <span class="text-xs text-gray-500 block mb-1">Catatan:</span>
                        <p class="text-gray-700 italic">&quot;{{ $detailNegotiation->notes }}&quot;</p>
                    </div>
                @endif
                <div class="text-[11px] text-gray-400 text-right">
                    Diajukan pada {{ \Carbon\Carbon::parse($detailNegotiation->created_at)->format('d M Y H:i') }} WIB
                </div>
            </div>
        @endif
        <x-slot:actions>
            <x-mary-button label="Tutup" @click="$wire.showDetailModal=false" class="btn-ghost text-black" />
        </x-slot:actions>
    </x-mary-modal>
</div>
