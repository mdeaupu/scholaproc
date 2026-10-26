<div>
@php
    $request = $procurementRequest;
    $canReview = $request->status === \App\Models\ProcurementRequest::STATUS_SUBMITTED;
    $canStartNegotiation = $request->status === \App\Models\ProcurementRequest::STATUS_VERIFIED;
@endphp

<x-mary-card title="Review Pengajuan" separator shadow>
    <div class="space-y-3">
        <p class="text-sm text-gray-600">Status: <span class="font-semibold">{{ $request->status_label }}</span></p>

        @if ($canReview)
            <x-mary-button label="Minta Revisi" icon="o-arrow-uturn-left"
                @click="$wire.revisionModal = true"
                class="w-full border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100" />
            <x-mary-button label="Tolak Pengajuan" icon="o-x-circle" @click="$wire.rejectModal = true"
                class="w-full border-red-200 bg-white text-red-600 hover:bg-red-50" />
            <x-mary-button label="Verifikasi Pengajuan" icon="o-check-circle" wire:click="verify"
                wire:confirm="Verifikasi kelengkapan pengajuan ini?" wire:loading.attr="disabled"
                spinner="verify" class="w-full bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
        @elseif ($canStartNegotiation)
            <p class="text-sm text-gray-600">Pengajuan telah diverifikasi dan siap diproses.</p>
            <x-mary-button label="Teruskan ke Negosiasi" icon="o-arrow-right"
                wire:click="startNegotiation"
                wire:confirm="Teruskan pengajuan ini ke tahap negosiasi?" wire:loading.attr="disabled"
                spinner="startNegotiation" class="w-full bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
        @else
            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500">
                Tidak ada tindakan review yang tersedia untuk status pengajuan saat ini.
            </p>
        @endif
    </div>
</x-mary-card>

<x-mary-modal wire:model="revisionModal" title="Minta Revisi Pengajuan"
    subtitle="Catatan ini akan disimpan pada riwayat dan ditampilkan kepada sekolah." class="backdrop-blur"
    title-class="text-[#0046FF]">
    <x-mary-form wire:submit="requestRevision">
        <x-mary-textarea label="Catatan Revisi" wire:model="revisionReason"
            placeholder="Jelaskan bagian yang perlu diperbaiki..." rows="4" required />
        @error('revisionReason')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.revisionModal = false" class="btn-ghost" />
            <x-mary-button label="Kirim Catatan Revisi" type="submit" icon="o-arrow-uturn-left"
                spinner="requestRevision" class="bg-[#0046FF] text-white" />
        </x-slot:actions>
    </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="rejectModal" title="Tolak Pengajuan"
    subtitle="Alasan penolakan akan disimpan pada riwayat pengajuan." class="backdrop-blur"
    title-class="text-red-600">
    <x-mary-form wire:submit="reject">
        <x-mary-textarea label="Alasan Penolakan" wire:model="rejectReason"
            placeholder="Masukkan alasan penolakan..." rows="4" required />
        @error('rejectReason')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.rejectModal = false" class="btn-ghost" />
            <x-mary-button label="Tolak Pengajuan" type="submit" icon="o-x-circle" spinner="reject"
                class="bg-red-600 text-white hover:bg-red-700" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-modal>
</div>
