<div>
@php
    $request = $procurementRequest;
    $canReview = $request->status === \App\Models\ProcurementRequest::STATUS_SUBMITTED;
    $canStartNegotiation = $request->canStartNegotiation();
    $hasIssuedDocuments = $request->officialDocuments()->whereNotNull('issued_at')->exists();
    $canChangeSubsidiary = $request->status === \App\Models\ProcurementRequest::STATUS_VERIFIED
        || ($request->status !== \App\Models\ProcurementRequest::STATUS_NEGOTIATION && $hasIssuedDocuments);
@endphp

<x-mary-card title="Review Pengajuan" separator shadow>
    <div class="space-y-3">
        <p class="text-sm text-gray-600">Status: <span class="font-semibold">{{ $request->status_label }}</span></p>

        @if (in_array($request->status, [
            \App\Models\ProcurementRequest::STATUS_VERIFIED,
            \App\Models\ProcurementRequest::STATUS_NEGOTIATION,
            \App\Models\ProcurementRequest::STATUS_PRICE_AGREED,
            \App\Models\ProcurementRequest::STATUS_PROCESSING,
        ], true))
            <div class="space-y-2 rounded-lg border border-blue-100 bg-blue-50 p-3">
                <p class="text-sm font-semibold">Anak Perusahaan wajib ditetapkan sebelum negosiasi</p>
                @if ($request->subsidiary)
                    <p class="text-sm">{{ $request->subsidiary->name }} · NPWP {{ $request->subsidiary->npwp }}</p>
                @elseif ($request->status === \App\Models\ProcurementRequest::STATUS_VERIFIED)
                    <p class="text-sm text-amber-700">Anak Perusahaan belum ditetapkan.</p>
                @endif
                @if ($canChangeSubsidiary)
                    <x-mary-select label="Anak Perusahaan aktif" wire:model="subsidiaryId"
                        :options="$activeSubsidiaries" option-value="id" option-label="name" placeholder="Pilih Anak Perusahaan" />
                    @if ($hasIssuedDocuments)
                        <x-mary-textarea label="Alasan koreksi penetapan" wire:model="assignmentReason" rows="2" />
                    @endif
                    <x-mary-button label="{{ $hasIssuedDocuments ? 'Simpan Koreksi Penetapan' : 'Simpan Penetapan' }}"
                        icon="o-building-office-2" wire:click="assignSubsidiary" spinner="assignSubsidiary"
                        class="w-full border border-blue-200 bg-white text-blue-700" />
                @endif
            </div>
        @endif

        @if ($canReview)
            <x-mary-button label="Minta Revisi" icon="o-arrow-uturn-left"
                @click="$wire.revisionModal = true"
                class="w-full border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100" />
            <x-mary-button label="Tolak Pengajuan" icon="o-x-circle" @click="$wire.rejectModal = true"
                class="w-full border-red-200 bg-white text-red-600 hover:bg-red-50" />
            <x-mary-button label="Verifikasi Pengajuan" icon="o-check-circle" wire:click="confirmVerify"
                wire:loading.attr="disabled" spinner="verify"
                class="w-full bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
        @elseif ($canStartNegotiation)
            <p class="text-sm text-gray-600">Pengajuan telah diverifikasi dan siap diproses.</p>
            <x-mary-button label="Teruskan ke Negosiasi" icon="o-arrow-right"
                wire:click="confirmStartNegotiation" wire:loading.attr="disabled"
                spinner="startNegotiation" class="w-full bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
        @else
            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500">
                Tidak ada tindakan review yang tersedia untuk status pengajuan saat ini.
            </p>
        @endif
    </div>
</x-mary-card>

<x-mary-card title="Konfigurasi Pajak Transaksi" separator shadow>
    @if ($request->isTaxConfigurationValidated())
        <p class="mb-3 text-sm text-green-700">
            Tervalidasi berdasarkan: {{ $request->tax_configuration_basis }}
        </p>
    @else
        <p class="mb-3 text-sm text-amber-700">
            Belum tervalidasi. Tarif pajak dan total final tidak akan dihitung atau ditampilkan sebagai nilai final.
        </p>
    @endif
    <p class="mb-3 text-sm text-gray-600">
        Tarif hanya diambil dari aturan pajak aktif yang telah disetujui dan diverifikasi.
    </p>
    <x-mary-button label="Evaluasi Aturan Pajak" icon="o-arrow-path"
        wire:click="evaluateTaxConfiguration" spinner="evaluateTaxConfiguration"
        class="w-full border border-blue-200 bg-white text-[#0046FF]" />
</x-mary-card>

<x-mary-modal wire:model="revisionModal" title="Minta Revisi Pengajuan" class="backdrop-blur"
    title-class="text-amber-700">
    <div class="flex items-start gap-4">
        <div class="shrink-0 rounded-full bg-amber-100 p-3 text-amber-600">
            <x-mary-icon name="o-arrow-uturn-left" class="h-7 w-7" />
        </div>
        <div class="w-full">
            <p class="font-semibold text-black">Minta revisi pengajuan ini?</p>
            <p class="mt-1 text-sm leading-relaxed text-gray-500">
                Catatan revisi akan disimpan pada riwayat dan ditampilkan kepada sekolah.
            </p>
            <x-mary-form wire:submit="requestRevision" class="mt-4 space-y-3">
                <x-mary-textarea label="Catatan Revisi" wire:model="revisionReason"
                    placeholder="Jelaskan bagian yang perlu diperbaiki..." rows="4" required />
                @error('revisionReason')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.revisionModal = false" class="btn-ghost text-black" />
                    <x-mary-button label="Kirim Catatan Revisi" type="submit" icon="o-arrow-uturn-left"
                        spinner="requestRevision" class="bg-amber-500 text-white hover:bg-amber-600" />
                </x-slot:actions>
            </x-mary-form>
        </div>
    </div>
</x-mary-modal>

<x-mary-modal wire:model="rejectModal" title="Tolak Pengajuan" class="backdrop-blur"
    title-class="text-red-600">
    <div class="flex items-start gap-4">
        <div class="shrink-0 rounded-full bg-red-100 p-3 text-red-600">
            <x-mary-icon name="o-x-circle" class="h-7 w-7" />
        </div>
        <div class="w-full">
            <p class="font-semibold text-black">Tolak pengajuan ini?</p>
            <p class="mt-1 text-sm leading-relaxed text-gray-500">
                Alasan penolakan akan disimpan pada riwayat pengajuan.
            </p>
            <x-mary-form wire:submit="reject" class="mt-4 space-y-3">
                <x-mary-textarea label="Alasan Penolakan" wire:model="rejectReason"
                    placeholder="Masukkan alasan penolakan..." rows="4" required />
                @error('rejectReason')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.rejectModal = false" class="btn-ghost text-black" />
                    <x-mary-button label="Tolak Pengajuan" type="submit" icon="o-x-circle" spinner="reject"
                        class="bg-red-600 text-white hover:bg-red-700" />
                </x-slot:actions>
            </x-mary-form>
        </div>
    </div>
</x-mary-modal>

<x-mary-modal wire:model="verifyModal" title="Verifikasi Pengajuan" class="backdrop-blur"
    title-class="text-[#0046FF]">
    <div class="flex items-start gap-4">
        <div class="shrink-0 rounded-full bg-[#0046FF]/10 p-3 text-[#0046FF]">
            <x-mary-icon name="o-check-circle" class="h-7 w-7" />
        </div>
        <div>
            <p class="font-semibold text-black">Verifikasi pengajuan ini?</p>
            <p class="mt-1 text-sm leading-relaxed text-gray-500">
                Kelengkapan dan kelayakan pengajuan akan dicatat sebagai terverifikasi.
            </p>
        </div>
    </div>
    <x-slot:actions>
        <x-mary-button label="Batal" wire:click="$set('verifyModal', false)" class="btn-ghost text-black" />
        <x-mary-button label="Ya, Verifikasi" wire:click="verify" wire:loading.attr="disabled"
            spinner="verify" class="bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
    </x-slot:actions>
</x-mary-modal>

<x-mary-modal wire:model="negotiationModal" title="Teruskan ke Negosiasi" class="backdrop-blur"
    title-class="text-[#0046FF]">
    <div class="flex items-start gap-4">
        <div class="shrink-0 rounded-full bg-[#0046FF]/10 p-3 text-[#0046FF]">
            <x-mary-icon name="o-chat-bubble-left-right" class="h-7 w-7" />
        </div>
        <div>
            <p class="font-semibold text-black">Mulai tahap negosiasi harga?</p>
            <p class="mt-1 text-sm leading-relaxed text-gray-500">
                Pengajuan akan diteruskan ke tahap negosiasi dan sekolah dapat mulai menanggapi penawaran harga.
            </p>
        </div>
    </div>
    <x-slot:actions>
        <x-mary-button label="Batal" wire:click="$set('negotiationModal', false)" class="btn-ghost text-black" />
        <x-mary-button label="Ya, Teruskan" wire:click="startNegotiation" wire:loading.attr="disabled"
            spinner="startNegotiation" class="bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
    </x-slot:actions>
</x-mary-modal>

</div>
