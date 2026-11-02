<div class="space-y-6">
    <x-mary-card title="Pemrosesan Pengadaan" subtitle="Status, pajak, negosiasi, dan riwayat transaksi." separator shadow>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Status pengadaan</p>
                <p class="font-semibold">{{ $procurementRequest->status_label }}</p>
            </div>
            <x-mary-badge :value="$procurementRequest->status_label" class="{{ $procurementRequest->status_pill_class }}" />
        </div>

        @if ($procurementRequest->canSubmit() && auth()->user()->isSchool())
            <x-mary-button class="mt-4" label="Kirim Pengajuan" icon="o-paper-airplane"
                wire:click="submitRequest" spinner="submitRequest" />
        @endif

        @if ($procurementRequest->canVerify() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
            <div class="mt-4 flex flex-wrap gap-2">
                <x-mary-button label="Verifikasi Pengajuan" icon="o-check-circle"
                    wire:click="verify" spinner="verify" />
                <x-mary-button label="Tolak Pengajuan" icon="o-x-circle"
                    @click="$wire.rejectModal=true" class="bg-red-600 text-white" />
            </div>
        @endif
    </x-mary-card>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-mary-card title="Anak Perusahaan" subtitle="Setiap transaksi wajib menggunakan satu entitas aktif." separator shadow>
            @if ($procurementRequest->hasSubsidiary())
                <p class="font-semibold">{{ $procurementRequest->subsidiary->name }}</p>
                <p class="mt-1 text-sm text-gray-500">NPWP: {{ $procurementRequest->subsidiary_snapshot['npwp'] ?? $procurementRequest->subsidiary->npwp }}</p>
            @else
                <p class="text-sm text-amber-700">Anak Perusahaan belum ditetapkan.</p>
            @endif

            @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_VERIFIED
                && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                <x-mary-button class="mt-4" label="Tetapkan Anak Perusahaan" icon="o-building-office-2"
                    @click="$wire.subsidiaryModal=true" />
            @endif
            @if ($procurementRequest->canStartNegotiation()
                && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                <x-mary-button class="mt-4" label="Mulai Negosiasi" icon="o-chat-bubble-left-right"
                    wire:click="startNegotiation" spinner="startNegotiation" />
            @endif
        </x-mary-card>

        <x-mary-card title="Evaluasi Pajak" subtitle="Tarif hanya diterapkan dari aturan pajak yang tervalidasi." separator shadow>
            <x-mary-badge
                :value="$procurementRequest->isTaxConfigurationValidated() ? 'Tervalidasi' : 'Perlu ditinjau'"
                class="{{ $procurementRequest->isTaxConfigurationValidated() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}" />
            <p class="mt-3 text-sm text-gray-600">{{ $taxEvaluationMessage }}</p>
            @if ((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $procurementRequest->hasSubsidiary())
                <x-mary-button class="mt-4" label="Evaluasi Aturan Pajak" icon="o-arrow-path"
                    wire:click="evaluateTaxes" spinner="evaluateTaxes" />
            @endif
        </x-mary-card>
    </div>

    <x-mary-card title="Daftar Barang" subtitle="Harga resmi ditetapkan melalui persetujuan negosiasi, bukan input manual." separator shadow>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-gray-500">
                        <th class="py-2 pr-4">Barang</th>
                        <th class="py-2 pr-4">Jumlah</th>
                        <th class="py-2 pr-4">Estimasi</th>
                        <th class="py-2 pr-4">Harga final</th>
                        <th class="py-2">Status negosiasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($procurementRequest->items as $item)
                        <tr class="border-b last:border-0">
                            <td class="py-3 pr-4">
                                <span class="font-medium">{{ $item->item_name }}</span>
                                <span class="block text-xs text-gray-500">{{ $item->specification }}</span>
                            </td>
                            <td class="py-3 pr-4">{{ $item->quantity }} {{ $item->unit?->name }}</td>
                            <td class="py-3 pr-4">Rp {{ number_format((float) $item->estimated_price, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4">
                                {{ $item->official_price !== null ? 'Rp '.number_format((float) $item->official_price, 0, ',', '.') : 'Belum disepakati' }}
                            </td>
                            <td class="py-3">{{ \Illuminate\Support\Str::headline($item->negotiation_status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>

    @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION
        && $procurementRequest->hasSubsidiary())
        <x-mary-card title="Negosiasi Harga" subtitle="Riwayat putaran tawaran dan persetujuan sekolah/penyedia." separator shadow>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600">
                    {{ $negotiationProgress['settled'] }}/{{ $negotiationProgress['total'] }} item selesai
                    ({{ $negotiationProgress['percentage'] }}%).
                </p>
                <x-mary-button
                    :label="$showNegotiation ? 'Tutup negosiasi' : 'Buka form dan riwayat negosiasi'"
                    icon="o-chat-bubble-left-right" wire:click="toggleNegotiation" />
            </div>
            @if ($showNegotiation)
                <livewire:procurement.negotiation-panel
                    :procurement-request="$procurementRequest"
                    wire:key="negotiation-panel-{{ $procurementRequest->id }}" />
            @endif
        </x-mary-card>
    @endif

    @if ($procurementRequest->canComplete() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
        <x-mary-button label="Selesaikan Pengadaan" icon="o-check-circle"
            wire:click="complete" spinner="complete" class="bg-green-700 text-white" />
    @endif

    <x-mary-card title="Riwayat Pengadaan" subtitle="Perubahan status dan aktivitas transaksi." separator shadow>
        @forelse ($histories as $history)
            <div class="border-b py-3 last:border-0">
                <p class="font-medium">{{ \Illuminate\Support\Str::headline($history->status) }}</p>
                @if ($history->notes)
                    <p class="mt-1 text-sm text-gray-600">{{ $history->notes }}</p>
                @endif
                <p class="mt-1 text-xs text-gray-500">
                    {{ $history->createdBy?->name ?? 'Sistem' }} · {{ $history->created_at?->format('d M Y H:i') }}
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500">Belum ada riwayat pengadaan.</p>
        @endforelse
    </x-mary-card>

    <x-mary-modal wire:model="subsidiaryModal" title="Tetapkan Anak Perusahaan">
        <x-mary-form wire:submit="assignSubsidiary">
            <x-mary-select label="Anak Perusahaan aktif" wire:model="subsidiaryId"
                :options="$subsidiaries" option-value="id" option-label="name" />
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.subsidiaryModal=false" class="btn-ghost" />
                <x-mary-button label="Tetapkan" type="submit" spinner="assignSubsidiary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="rejectModal" title="Tolak Pengajuan">
        <x-mary-form wire:submit="reject">
            <x-mary-textarea label="Alasan penolakan" wire:model="rejectReason" rows="3" />
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.rejectModal=false" class="btn-ghost" />
                <x-mary-button label="Tolak Pengajuan" type="submit" spinner="reject" class="bg-red-600 text-white" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
