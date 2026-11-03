<div class="space-y-5">
    <x-mary-card title="Pemrosesan Pengadaan" subtitle="Status, pajak, negosiasi, dan riwayat transaksi." separator shadow>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Status pengadaan</p>
                <p class="font-semibold">{{ $procurementRequest->status_label }}</p>
            </div>
            <x-mary-badge :value="$procurementRequest->status_label" class="{{ $procurementRequest->status_pill_class }}" />
        </div>
    </x-mary-card>

    <nav class="flex overflow-x-auto border-b border-gray-200 bg-white" aria-label="Navigasi pemrosesan pengadaan">
        @php
            $tabs = [
                ['key' => 'informasi', 'label' => 'Informasi & Barang', 'icon' => 'o-information-circle'],
                ['key' => 'negosiasi', 'label' => 'Ruang Negosiasi', 'icon' => 'o-chat-bubble-left-right'],
                ['key' => 'pengiriman', 'label' => 'Pengiriman & Penerimaan', 'icon' => 'o-truck'],
                ['key' => 'dokumen', 'label' => 'Dokumen', 'icon' => 'o-document-text'],
            ];
        @endphp
        @foreach ($tabs as $tab)
            <button type="button" wire:click="setActiveTab('{{ $tab['key'] }}')"
                @if ($activeTab === $tab['key']) aria-current="page" @endif
                class="flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors
                    {{ $activeTab === $tab['key']
                        ? 'border-[#0046FF] text-[#0046FF]'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                <x-mary-icon :name="$tab['icon']" class="h-5 w-5" />
                <span>{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </nav>

    @if ($activeTab === 'informasi')
        <div class="space-y-5">
            <div class="grid gap-5 xl:grid-cols-2">
                <x-mary-card title="Anak Perusahaan" subtitle="Setiap transaksi wajib menggunakan satu entitas aktif." separator shadow>
                    @if ($procurementRequest->hasSubsidiary())
                        <p class="font-semibold">{{ $procurementRequest->subsidiary->name }}</p>
                        <p class="mt-1 text-sm text-gray-500">
                            NPWP: {{ $procurementRequest->subsidiary_snapshot['npwp'] ?? $procurementRequest->subsidiary->npwp }}
                        </p>
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

            @if ($procurementRequest->canSubmit() && auth()->user()->isSchool())
                <x-mary-button label="Kirim Pengajuan" icon="o-paper-airplane"
                    wire:click="submitRequest" spinner="submitRequest" />
            @endif

            @if ($procurementRequest->canVerify() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                <div class="flex flex-wrap gap-2">
                    <x-mary-button label="Verifikasi Pengajuan" icon="o-check-circle"
                        wire:click="verify" spinner="verify" />
                    <x-mary-button label="Tolak Pengajuan" icon="o-x-circle"
                        @click="$wire.rejectModal=true" class="bg-red-600 text-white" />
                </div>
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
        </div>
    @endif

    @if ($activeTab === 'negosiasi')
        @if ($procurementRequest->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION
            && $procurementRequest->hasSubsidiary())
            <livewire:procurement.negotiation-panel
                :procurement-request="$procurementRequest"
                wire:key="negotiation-panel-{{ $procurementRequest->id }}" />
        @else
            <x-mary-card title="Ruang Negosiasi" separator shadow>
                <p class="text-sm text-gray-600">
                    Ruang negosiasi tersedia setelah pengajuan diteruskan ke tahap negosiasi dan Anak Perusahaan ditetapkan.
                </p>
            </x-mary-card>
        @endif
    @endif

    @if ($activeTab === 'pengiriman')
        @if (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <livewire:procurement.procurement-processing-panel
                :procurement-request="$procurementRequest"
                wire:key="processing-panel-{{ $procurementRequest->id }}" />
        @else
            <div class="space-y-5">
                <x-mary-card title="Pengiriman" separator shadow>
                    @forelse ($procurementRequest->deliveries as $delivery)
                        <div class="border-b py-3 text-sm last:border-0">
                            <p class="font-medium">{{ $delivery->delivered_at?->format('d-m-Y') ?? '-' }}</p>
                            <p class="text-gray-500">{{ $delivery->items->sum('quantity') }} unit dikirim</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada pengiriman yang tercatat.</p>
                    @endforelse
                </x-mary-card>

                <x-mary-card title="Penerimaan Barang" separator shadow>
                    @forelse ($procurementRequest->receipts as $receipt)
                        <div class="border-b py-3 text-sm last:border-0">
                            <p class="font-medium">{{ $receipt->received_at?->format('d-m-Y') ?? '-' }}</p>
                            <p class="text-gray-500">
                                {{ $receipt->items->sum('quantity') }} unit · {{ $receipt->verified_at ? 'Terverifikasi' : 'Dalam pemeriksaan' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada penerimaan barang yang tercatat.</p>
                    @endforelse
                </x-mary-card>
            </div>
        @endif
    @endif

    @if ($activeTab === 'dokumen')
        <x-mary-card title="Dokumen Terbit" subtitle="Dokumen transaksi yang telah diterbitkan." separator shadow>
            <ul class="space-y-3 text-sm">
                @forelse ($procurementRequest->documents->whereNotNull('issued_at') as $document)
                    <li class="flex flex-wrap items-center justify-between gap-2 border-b pb-3 last:border-0">
                        <span>
                            {{ strtoupper(str_replace('_', ' ', $document->document_type)) }}
                            · {{ $document->document_number }}
                        </span>
                        <a class="font-medium text-blue-700 underline"
                            href="{{ route('procurement.documents.download', [$procurementRequest, $document]) }}">
                            Unduh
                        </a>
                    </li>
                @empty
                    <li class="text-gray-500">Belum ada dokumen yang diterbitkan.</li>
                @endforelse
            </ul>
        </x-mary-card>
    @endif

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
