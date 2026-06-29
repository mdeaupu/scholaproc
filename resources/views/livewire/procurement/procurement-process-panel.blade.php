<x-mary-card title="Panel Proses Pengadaan" subtitle="Kelola alur pengadaan berdasarkan status saat ini." shadow separator
    class="border-t-4 border-[#0046FF]">
    @php
        $theme = match ($procurementRequest->status) {
            'draft' => [
                'alert' => 'bg-gray-100 text-gray-700',
                'badge' => 'bg-gray-200 text-gray-700 border-none',
            ],
            'submitted', 'verified', 'items_prepared', 'completed' => [
                'alert' => 'bg-[#0046FF]/10 text-[#0046FF]',
                'badge' => 'bg-[#0046FF] text-white border-none',
            ],
            'supplier_assigned', 'rejected' => [
                'alert' => 'bg-[#FF8040]/10 text-[#FF8040]',
                'badge' => 'bg-[#FF8040] text-white border-none',
            ],
            default => [
                'alert' => 'bg-gray-100 text-gray-700',
                'badge' => 'badge-ghost',
            ],
        };
    @endphp
    <x-mary-alert icon="o-information-circle" class="{{ $theme['alert'] }} border-none mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <p class="font-semibold">Status Pengadaan</p>
                <p class="text-sm opacity-80">{{ Str::headline($procurementRequest->status) }}</p>
            </div>
            <x-mary-badge :value="Str::headline($procurementRequest->status)" class="{{ $theme['badge'] }} badge-lg font-semibold" />
        </div>
    </x-mary-alert>
    @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_REJECTED)
        @php
            $latestReject = $procurementRequest->histories
                ->where('status', \App\Models\ProcurementRequest::STATUS_REJECTED)
                ->sortByDesc('id')
                ->first();
        @endphp
        <x-mary-alert icon="o-x-circle" class="bg-[#FF8040] mb-6 text-white border-none shadow-sm">
            <div>
                <p class="font-bold text-base">Pengajuan Ditolak</p>
                <p class="text-sm opacity-90 mt-1">
                    <strong>Keterangan:</strong>
                    {{ $latestReject?->notes ?? 'Tidak ada alasan spesifik yang dicantumkan.' }}
                </p>
                @if ($latestReject?->createdBy)
                    <p class="text-xs opacity-75 mt-2 italic">
                        Ditolak oleh: {{ $latestReject->createdBy->name }} pada
                        {{ \Carbon\Carbon::parse($latestReject->created_at)->format('d M Y H:i') }} WIB
                    </p>
                @endif
            </div>
        </x-mary-alert>
    @endif
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-bold text-lg text-black">Daftar Barang</h3>
                    <p class="text-sm text-gray-500">Barang yang diajukan oleh sekolah.</p>
                </div>
                <div class="p-5">
                    <x-mary-table :headers="[
                        ['key' => 'item_name', 'label' => 'Nama Barang'],
                        ['key' => 'specification', 'label' => 'Spesifikasi'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'unit', 'label' => 'Satuan'],
                        ['key' => 'estimated_price', 'label' => 'Harga Estimasi'],
                        ['key' => 'subtotal', 'label' => 'Subtotal (Est)'],
                    ]" :rows="$items" striped hover class="text-black">
                        @scope('cell_estimated_price', $item)
                            Rp {{ number_format($item->estimated_price, 0, ',', '.') }}
                        @endscope
                        @scope('cell_subtotal', $item)
                            Rp {{ number_format($item->estimatedAmount(), 0, ',', '.') }}
                        @endscope
                    </x-mary-table>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5 mt-6">
                <h3 class="font-bold text-lg mb-4 text-black">Informasi Nilai Kontrak & Perpajakan Resmi (M6)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Subtotal Estimasi (Sekolah):</span>
                        <span class="font-semibold text-black">Rp
                            {{ number_format($procurementRequest->estimatedSubtotal(), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Subtotal Resmi (Supplier):</span>
                        <span class="font-semibold text-black">Rp
                            {{ number_format($procurementRequest->officialSubtotal(), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Status Pajak Pengadaan:</span>
                        <span
                            class="font-semibold {{ $procurementRequest->is_taxable ? 'text-[#0046FF]' : 'text-gray-600' }}">
                            {{ $procurementRequest->is_taxable ? 'Kena Pajak (PKP)' : 'Non-PKP' }}
                        </span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">PPN ({{ (float) $procurementRequest->ppn_rate }}%):</span>
                        <span class="font-semibold text-[#FF8040]">+ Rp
                            {{ number_format($procurementRequest->totalPpn(), 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col items-end">
                    <div class="text-xs text-gray-500">Grand Total Pengadaan (Inc. PPN):</div>
                    <div class="text-2xl font-bold text-[#0046FF]">Rp
                        {{ number_format($procurementRequest->grandTotal(), 0, ',', '.') }}</div>
                    <div class="text-sm font-medium text-gray-600 mt-1">
                        Nilai Bersih Diterima Supplier (Netto): Rp
                        {{ number_format($procurementRequest->netTotal(), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 shadow-sm">
                <div class="p-5 border-b border-gray-200 bg-white rounded-t-xl">
                    <h3 class="font-bold text-black">Panel Aksi</h3>
                    <p class="text-sm text-gray-500">Aksi yang tersedia sesuai status pengadaan.</p>
                </div>
                <div class="p-5 space-y-3">
                    <x-mary-button label="Kembali" icon="o-arrow-left" link="{{ route('procurement.index') }}"
                        wire:navigate
                        class="btn-ghost bg-white border-gray-300 text-black hover:border-[#0046FF] hover:text-[#0046FF] w-full" />
                    @if (
                        $procurementRequest->status === \App\Models\ProcurementRequest::STATUS_DRAFT &&
                            auth()->user()->can('admin-school-only'))
                        <x-mary-button label="Edit Pengajuan" icon="o-pencil"
                            link="{{ route('procurement.edit', $procurementRequest->id) }}" wire:navigate
                            class="bg-white border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF]/10 w-full" />
                    @endif
                    @can('admin-school-only')
                        @if ($procurementRequest->canSubmit())
                            <x-mary-button label="Submit Pengajuan" icon="o-paper-airplane" wire:click="submitRequest"
                                wire:loading.attr="disabled" spinner="submitRequest"
                                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full" />
                        @endif
                    @endcan
                    @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                        @if ($procurementRequest->canVerify())
                            <x-mary-button label="Verifikasi" icon="o-check-circle" wire:click="verify"
                                wire:loading.attr="disabled" spinner="verify"
                                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full" />
                            <x-mary-button label="Tolak Pengajuan" icon="o-x-circle" @click="$wire.rejectModal=true"
                                wire:loading.attr="disabled"
                                class="bg-[#FF8040] hover:bg-[#FF8040]/90 text-white border-none w-full" />
                        @endif
                        @if ($procurementRequest->canAssignSupplier())
                            <x-mary-button label="Pilih Supplier" icon="o-truck" @click="$wire.supplierModal=true"
                                wire:loading.attr="disabled"
                                class="bg-white border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF]/10 w-full" />
                        @endif
                        @if ($procurementRequest->canPrepareItems())
                            <x-mary-button label="Barang Sudah Disiapkan" icon="o-cube" wire:click="markItemsPrepared"
                                wire:loading.attr="disabled" spinner="markItemsPrepared"
                                class="bg-white border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF]/10 w-full" />
                        @endif
                        @if ($procurementRequest->canComplete())
                            <x-mary-button label="Selesaikan Pengadaan" icon="o-document-check" wire:click="complete"
                                wire:loading.attr="disabled" spinner="complete"
                                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full" />
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    <x-mary-modal wire:model="rejectModal" title="Tolak Pengajuan" class="backdrop-blur" title-class="text-[#FF8040]">
        <x-mary-form wire:submit="reject">
            <x-mary-textarea label="Alasan Penolakan" wire:model="rejectReason"
                placeholder="Masukkan alasan penolakan..." required />
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.rejectModal=false" class="btn-ghost text-black" />
                <x-mary-button label="Tolak" type="submit" spinner="reject"
                    class="bg-[#FF8040] text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
    <x-mary-modal wire:model="supplierModal" title="Pilih Supplier" class="backdrop-blur text-black"
        title-class="text-[#0046FF]">
        <x-mary-form wire:submit="assignSupplier">
            <x-mary-choices label="Supplier" wire:model="supplierId" :options="$suppliers" option-label="company_name"
                option-value="id" single required />
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.supplierModal=false" class="btn-ghost text-black" />
                <x-mary-button label="Simpan" type="submit" spinner="assignSupplier"
                    class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</x-mary-card>
