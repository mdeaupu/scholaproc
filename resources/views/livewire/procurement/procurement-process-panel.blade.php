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
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
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
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                <h3 class="font-bold text-black text-sm mb-1">Langkah Administrasi Berkas (M7)</h3>
                <p class="text-xs text-gray-500 mb-4">Lengkapi prasyarat di bawah untuk mengaktifkan cetak surat.</p>
                <div
                    class="space-y-4 relative before:absolute before:bottom-2 before:top-2 before:left-3.25 before:w-0.5 before:bg-gray-200">
                    <div class="flex gap-3 relative">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold z-10 
                            {{ $procurementRequest->hasSupplier() ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            @if ($procurementRequest->hasSupplier())
                                ✓
                            @else
                                1
                            @endif
                        </div>
                        <div class="flex-1 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-black">Penunjukan Supplier</span>
                                @if ($procurementRequest->canAssignSupplier() && (auth()->user()->isOwner() || auth()->user()->isAdminCv()))
                                    <x-mary-button label="Pilih" icon="o-truck" @click="$wire.supplierModal=true"
                                        class="btn-xs bg-white text-[#0046FF] border-[#0046FF] hover:bg-[#0046FF]/10" />
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                {{ $procurementRequest->hasSupplier() ? $procurementRequest->supplier->company_name : 'Belum ditunjuk.' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 relative">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold z-10 
                            {{ $procurementRequest->is_taxable || $procurementRequest->ppn_rate > 0 ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            @if ($procurementRequest->is_taxable || $procurementRequest->ppn_rate > 0)
                                ✓
                            @else
                                2
                            @endif
                        </div>
                        <div class="flex-1 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-black">Komponen Perpajakan</span>
                                @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                                    <x-mary-button label="Atur" icon="o-ticket" @click="$wire.taxModal=true"
                                        class="btn-xs bg-white text-gray-600 border-gray-300 hover:border-[#0046FF]" />
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                Status: <span
                                    class="font-medium text-black">{{ $procurementRequest->is_taxable ? 'Kena Pajak (Inc. PPN)' : 'Non-PKP / Belum Diatur' }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 relative">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold z-10 
                            {{ $procurementRequest->hasSignatories() ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            @if ($procurementRequest->hasSignatories())
                                ✓
                            @else
                                3
                            @endif
                        </div>
                        <div class="flex-1 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-black">Pejabat Penandatangan</span>
                                @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                                    <x-mary-button label="Input" icon="o-user-group" @click="$wire.signatoryModal=true"
                                        class="btn-xs bg-white text-gray-600 border-gray-300 hover:border-[#0046FF]" />
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                {{ $procurementRequest->hasSignatories() ? 'Lengkap (3 Perangkat Pejabat)' : 'Belum melengkapi data.' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 relative">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold z-10 
        {{ $procurementRequest->hasOfficialPrices() ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            @if ($procurementRequest->hasOfficialPrices())
                                ✓
                            @else
                                4
                            @endif
                        </div>
                        <div class="flex-1 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-black block">Validasi Nominal Kontrak</span>
                                @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                                    <x-mary-button label="Input Harga" icon="o-currency-dollar"
                                        wire:click="openPriceModal"
                                        class="btn-xs bg-white text-gray-600 border-gray-300 hover:border-[#0046FF]" />
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                {{ $procurementRequest->hasOfficialPrices() ? 'Harga negosiasi/resmi telah disetujui.' : 'Admin CV harus menginput harga penawaran.' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 relative">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold z-10 
        {{ $procurementRequest->documents()->exists() ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            @if ($procurementRequest->documents()->exists())
                                ✓
                            @else
                                5
                            @endif
                        </div>
                        <div class="flex-1 bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                            <span class="text-xs font-semibold text-black block mb-2">Penomoran Dokumen Dinas</span>
                            @if ($procurementRequest->documents()->exists())
                                <p class="text-[10px] text-emerald-600 font-medium">Dokumen resmi sudah diterbitkan.</p>
                            @else
                                @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                                    <x-mary-button label="Generate Nomor Surat" icon="o-identification"
                                        wire:click="setDocumentNumbers" wire:loading.attr="disabled"
                                        spinner="setDocumentNumbers" @disabled(!$procurementRequest->hasOfficialPrices())
                                        class="w-full btn-xs bg-[#0046FF] text-white hover:bg-[#0046FF]/95 border-none" />
                                @else
                                    <p class="text-[10px] text-gray-500">Menunggu Admin CV menerbitkan nomor surat.</p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 shadow-sm p-4 space-y-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Alur Kerja
                    Utama</span>
                <x-mary-button label="Kembali" icon="o-arrow-left" link="{{ route('procurement.index') }}"
                    wire:navigate
                    class="btn-ghost bg-white border-gray-300 text-black hover:border-[#0046FF] hover:text-[#0046FF] w-full btn-sm" />
                @if (
                    $procurementRequest->status === \App\Models\ProcurementRequest::STATUS_DRAFT &&
                        auth()->user()->can('admin-school-only'))
                    <x-mary-button label="Edit Pengajuan" icon="o-pencil"
                        link="{{ route('procurement.edit', $procurementRequest->id) }}" wire:navigate
                        class="bg-white border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF]/10 w-full btn-sm" />
                @endif
                @can('admin-school-only')
                    @if ($procurementRequest->canSubmit())
                        <x-mary-button label="Submit Pengajuan" icon="o-paper-airplane" wire:click="submitRequest"
                            wire:loading.attr="disabled" spinner="submitRequest"
                            class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full btn-sm" />
                    @endif
                @endcan
                @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                    @if ($procurementRequest->canVerify())
                        <x-mary-button label="Verifikasi Sesuai" icon="o-check-circle" wire:click="verify"
                            wire:loading.attr="disabled" spinner="verify"
                            class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full btn-sm" />
                        <x-mary-button label="Tolak Pengajuan" icon="o-x-circle" @click="$wire.rejectModal=true"
                            wire:loading.attr="disabled"
                            class="bg-[#FF8040] hover:bg-[#FF8040]/90 text-white border-none w-full btn-sm" />
                    @endif
                    @if ($procurementRequest->canPrepareItems())
                        @if ($procurementRequest->documents()->exists())
                            <x-mary-button label="Barang Sudah Disiapkan" icon="o-cube"
                                wire:click="markItemsPrepared" wire:loading.attr="disabled"
                                spinner="markItemsPrepared"
                                class="bg-white border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF]/10 w-full btn-sm" />
                        @else
                            <div
                                class="p-3 bg-white rounded-lg border border-dashed border-amber-300 text-center flex flex-col items-center justify-center mt-2">
                                <x-mary-icon name="o-lock-closed" class="w-5 h-5 text-amber-500 mb-1" />
                                <p class="text-[10px] text-amber-700 leading-tight mt-1">
                                    <strong>Administrasi Belum Lengkap:</strong><br>
                                    Nomor Surat Resmi (Langkah 5) harus digenerate sebelum supplier menyiapkan barang.
                                </p>
                            </div>
                        @endif
                    @endif
                    @if ($procurementRequest->canComplete())
                        <x-mary-button label="Selesaikan Pengadaan" icon="o-document-check" wire:click="complete"
                            wire:loading.attr="disabled" spinner="complete"
                            class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none w-full btn-sm" />
                    @endif
                @endif
            </div>
        </div>
    </div>
    <x-mary-modal wire:model="taxModal" title="Atur Komponen Pajak Global" class="backdrop-blur text-black"
        title-class="text-[#0046FF]">
        <x-mary-form wire:submit="setTaxes">
            <x-mary-checkbox label="Transaksi Kena Pajak (PKP)" wire:model.live="isTaxable"
                class="checkbox-primary" />
            @if ($isTaxable)
                <div class="grid grid-cols-1 gap-4 mt-2">
                    <x-mary-input label="Tarif PPN (%)" type="number" step="0.1" wire:model="ppnRate" />
                    <x-mary-input label="Tarif PPh Pasal 22 (%)" type="number" step="0.01"
                        wire:model="pph22Rate" />
                    <x-mary-input label="Tarif PPh Pasal 23 (%)" type="number" step="0.01"
                        wire:model="pph23Rate" />
                </div>
            @endif
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.taxModal=false" class="btn-ghost text-black" />
                <x-mary-button label="Terapkan Pajak" type="submit" spinner="setTaxes"
                    class="bg-[#0046FF] text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
    <x-mary-modal wire:model="signatoryModal" title="Kelola Penandatangan Berkas" class="backdrop-blur text-black"
        title-class="text-[#0046FF]">
        <x-mary-form wire:submit="setSignatories">
            @foreach ($signatoriesData as $role => $fields)
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 mb-2">
                    <p class="font-bold text-xs uppercase text-[#0046FF] mb-2">{{ Str::headline($role) }}</p>
                    <div class="space-y-2">
                        <x-mary-input label="Nama Pejabat" wire:model="signatoriesData.{{ $role }}.name"
                            placeholder="Nama Lengkap & Gelar" required />
                        <x-mary-input label="NIP / Identitas Pegawai"
                            wire:model="signatoriesData.{{ $role }}.nip"
                            placeholder="Masukkan NIP jika ada" />
                        <x-mary-input label="Jabatan Resmi Dinas"
                            wire:model="signatoriesData.{{ $role }}.title"
                            placeholder="Contoh: Kepala Sekolah" required />
                    </div>
                </div>
            @endforeach
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.signatoryModal=false" class="btn-ghost text-black" />
                <x-mary-button label="Simpan Perangkat" type="submit" spinner="setSignatories"
                    class="bg-[#0046FF] text-white border-none" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
    <x-mary-modal wire:model="rejectModal" title="Tolak Pengajuan" class="backdrop-blur"
        title-class="text-[#FF8040]">
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
    <x-mary-modal wire:model="priceModal" title="Input Harga Penawaran (Resmi)" class="backdrop-blur text-black"
        title-class="text-[#0046FF]">
        <div class="mb-4 p-3 bg-blue-50 text-blue-800 text-xs rounded-lg border border-blue-100">
            Silakan sesuaikan harga penawaran final dari CV. Harga ini akan menjadi dasar perhitungan pajak dan nilai
            yang tercetak di dokumen SPK/BAST.
        </div>
        <x-mary-form wire:submit="saveOfficialPrices">
            <div class="max-h-[50vh] overflow-y-auto space-y-3 pr-2">
                @foreach ($procurementRequest->items as $item)
                    <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                        <p class="text-xs font-bold text-black mb-1">{{ $item->item_name }}</p>
                        <div
                            class="flex justify-between items-center text-[10px] text-gray-500 mb-2 border-b border-gray-200 pb-2">
                            <span>Qty: {{ $item->quantity }} {{ $item->unit }}</span>
                            <span>Estimasi Sekolah: Rp {{ number_format($item->estimated_price, 0, ',', '.') }}</span>
                        </div>
                        <x-mary-input label="Harga Resmi Satuan (Rp)" type="number"
                            wire:model="inputPrices.{{ $item->id }}" required prefix="Rp" />
                    </div>
                @endforeach
            </div>
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.priceModal=false" class="btn-ghost text-black" />
                <x-mary-button label="Simpan Harga Resmi" type="submit" spinner="saveOfficialPrices"
                    class="bg-[#0046FF] text-white border-none" />
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
