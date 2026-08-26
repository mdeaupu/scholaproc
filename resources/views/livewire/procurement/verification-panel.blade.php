@php
    $user = auth()->user();
    $isSchool = $user->isSchool();
    $isVerified = $procurementRequest->verified_at !== null;
    $totalFiles = $photos->count() + $documents->count();
    $hasSignedDoc = $procurementRequest->hasVerificationEvidence();
    $getFileUrl = fn ($path) => asset('storage/' . $path);
    $isPdf = fn ($filePath) => \Illuminate\Support\Str::endsWith($filePath, '.pdf');
@endphp

<div class="space-y-6">
    {{-- ─── Header & Status ─────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-bold text-lg text-black">Verifikasi Penerimaan Barang</h3>
                <p class="text-sm text-gray-500">Unggah bukti fisik penerimaan barang dan konfirmasi verifikasi.</p>
            </div>
            <x-mary-badge
                :value="$isVerified ? 'Terverifikasi' : $totalFiles . ' File'"
                class="{{ $isVerified ? 'bg-emerald-500 text-white' : 'bg-[#0046FF] text-white' }} badge-lg font-semibold" />
        </div>

        @if ($isVerified)
            <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-emerald-700">Barang Telah Diverifikasi Diterima</p>
                        <p class="text-sm text-emerald-600">
                            Diverifikasi oleh {{ $procurementRequest->verifiedBy?->name ?? '-' }}
                            pada {{ \Carbon\Carbon::parse($procurementRequest->verified_at)->format('d M Y H:i') }} WIB
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="mt-4 p-4 bg-[#0046FF]/5 border border-[#0046FF]/20 rounded-xl">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-[#0046FF] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-[#0046FF]">Menunggu Verifikasi</p>
                        <p class="text-sm text-[#0046FF]/70">
                            Unggah bukti foto dan dokumen bertanda tangan, lalu konfirmasi penerimaan.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ─── Upload Section (only for school, before verified) ────── --}}
    @if ($isSchool && !$isVerified)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Photo Upload --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                <h4 class="font-bold text-sm text-black mb-1">Foto Barang Fisik</h4>
                <p class="text-xs text-gray-500 mb-4">Unggah foto kondisi barang saat diterima (JPG/PNG, maks 5MB).</p>

                <div wire:submit="uploadPhoto" class="space-y-3">
                    <div
                        x-data="{ isDragging: false }"
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave="isDragging = false"
                        x-on:drop.prevent="isDragging = false"
                        :class="isDragging ? 'border-[#0046FF] bg-[#0046FF]/5' : 'border-gray-300 bg-gray-50 hover:border-[#0046FF]/50'"
                        class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors">
                        <input type="file" multiple accept="image/jpeg,image/png"
                            wire:model="photoFiles"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-500">Seret & lepas atau <span class="text-[#0046FF] font-medium">klik untuk memilih</span></p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG - Maks 5MB per file</p>
                    </div>

                    @if ($photoFiles)
                        <div class="space-y-2">
                            @foreach ($photoFiles as $index => $photo)
                                <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        @if ($photo->isPreviewable())
                                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover" />
                                        @else
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-black truncate">{{ $photo->getClientOriginalName() }}</p>
                                        <p class="text-[10px] text-gray-400">{{ round($photo->getSize() / 1024) }} KB</p>
                                    </div>
                                    <button wire:click="$set('photoFiles.{{ $index }}', null)" class="text-gray-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <x-mary-input label="Catatan Foto (Opsional)" wire:model="photoNotes"
                        placeholder="Contoh: Foto kondisi barang saat dikirim" />

                    <div class="flex justify-end">
                        <x-mary-button label="Unggah Foto" icon="o-cloud-arrow-up" type="submit"
                            wire:loading.attr="disabled" spinner="uploadPhoto"
                            class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none btn-sm"
                            @disabled="empty($photoFiles)" />
                    </div>
                </div>
            </div>

            {{-- Signed Document Upload --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                <h4 class="font-bold text-sm text-black mb-1">Dokumen Bertanda Tangan</h4>
                <p class="text-xs text-gray-500 mb-4">Unggah scan BAST, kuitansi, atau dokumen lain yang sudah ditandatangani (PDF/JPG/PNG, maks 10MB).</p>

                <div wire:submit="uploadDocument" class="space-y-3">
                    <div
                        x-data="{ isDragging: false }"
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave="isDragging = false"
                        x-on:drop.prevent="isDragging = false"
                        :class="isDragging ? 'border-[#FF8040] bg-[#FF8040]/5' : 'border-gray-300 bg-gray-50 hover:border-[#FF8040]/50'"
                        class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors">
                        <input type="file" multiple accept="application/pdf,image/jpeg,image/png"
                            wire:model="documentFiles"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-500">Seret & lepas atau <span class="text-[#FF8040] font-medium">klik untuk memilih</span></p>
                        <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG - Maks 10MB per file</p>
                    </div>

                    @if ($documentFiles)
                        <div class="space-y-2">
                            @foreach ($documentFiles as $index => $doc)
                                <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        @if ($doc->isPreviewable())
                                            <img src="{{ $doc->temporaryUrl() }}" class="w-full h-full object-cover" />
                                        @else
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-black truncate">{{ $doc->getClientOriginalName() }}</p>
                                        <p class="text-[10px] text-gray-400">{{ round($doc->getSize() / 1024) }} KB</p>
                                    </div>
                                    <button wire:click="$set('documentFiles.{{ $index }}', null)" class="text-gray-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <x-mary-input label="Catatan Dokumen (Opsional)" wire:model="documentNotes"
                        placeholder="Contoh: Scan BAST yang sudah ditandatangani" />

                    <div class="flex justify-end">
                        <x-mary-button label="Unggah Dokumen" icon="o-cloud-arrow-up" type="submit"
                            wire:loading.attr="disabled" spinner="uploadDocument"
                            class="bg-[#FF8040] hover:bg-[#FF8040]/90 text-white border-none btn-sm"
                            @disabled="empty($documentFiles)" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ─── Uploaded Files List ──────────────────────────────────── --}}
    @if ($totalFiles > 0)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
            <h4 class="font-bold text-sm text-black mb-4">File Bukti Penerimaan ({{ $totalFiles }})</h4>

            {{-- Photos --}}
            @if ($photos->isNotEmpty())
                <div class="mb-5">
                    <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Foto Barang ({{ $photos->count() }})</h5>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach ($photos as $photo)
                            <div class="relative group border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                                <a href="{{ $getFileUrl($photo->file_path) }}" target="_blank" class="block aspect-square">
                                    <img src="{{ $getFileUrl($photo->file_path) }}" alt="Foto barang"
                                        class="w-full h-full object-cover hover:opacity-90 transition-opacity" />
                                </a>
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors pointer-events-none"></div>
                                @if ($isSchool && !$isVerified)
                                    <button wire:click="openDeleteModal({{ $photo->id }})"
                                        class="absolute top-2 right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @endif
                                <div class="p-2">
                                    <p class="text-[10px] text-gray-500 truncate">{{ $photo->uploader?->name ?? '-' }}</p>
                                    <p class="text-[9px] text-gray-400">{{ $photo->uploaded_at?->format('d M Y H:i') ?? '-' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Signed Documents --}}
            @if ($documents->isNotEmpty())
                <div>
                    <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Dokumen Bertanda Tangan ({{ $documents->count() }})</h5>
                    <div class="space-y-2">
                        @foreach ($documents as $doc)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-[#FF8040]/10 flex items-center justify-center flex-shrink-0">
                                    @if ($isPdf($doc->file_path))
                                        <svg class="w-5 h-5 text-[#FF8040]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-[#FF8040]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $getFileUrl($doc->file_path) }}" target="_blank"
                                        class="text-sm font-medium text-black hover:text-[#0046FF] underline truncate block">
                                        {{ basename($doc->file_path) }}
                                    </a>
                                    <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-0.5">
                                        <span>Oleh: {{ $doc->uploader?->name ?? '-' }}</span>
                                        <span>&middot;</span>
                                        <span>{{ $doc->uploaded_at?->format('d M Y H:i') ?? '-' }}</span>
                                    </div>
                                    @if ($doc->notes)
                                        <p class="text-[10px] text-gray-400 italic mt-0.5">{{ $doc->notes }}</p>
                                    @endif
                                </div>
                                @if ($isSchool && !$isVerified)
                                    <button wire:click="openDeleteModal({{ $doc->id }})"
                                        class="text-gray-400 hover:text-red-500 flex-shrink-0 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ─── Confirm Verification Button ──────────────────────────── --}}
    @if ($isSchool && !$isVerified && $totalFiles > 0)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-sm text-black">Konfirmasi Penerimaan Barang</h4>
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($hasSignedDoc)
                            Dokumen bertanda tangan sudah tersedia. Klik tombol untuk mengonfirmasi bahwa barang telah diterima.
                        @else
                            <span class="text-[#FF8040]">Unggah minimal 1 dokumen bertanda tangan (BAST/Kuitansi) terlebih dahulu.</span>
                        @endif
                    </p>
                </div>
                <x-mary-button label="Tandai Sudah Diterima" icon="o-check-circle"
                    wire:click="openConfirmModal"
                    class="{{ $hasSignedDoc ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-none' : 'bg-gray-200 text-gray-400 border-none cursor-not-allowed' }}"
                    @disabled="!$hasSignedDoc" />
            </div>
        </div>
    @endif

    {{-- ─── Delete Confirmation Modal ────────────────────────────── --}}
    <x-mary-modal wire:model="showDeleteModal" title="Hapus File" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <p class="text-sm text-gray-600">Apakah Anda yakin ingin menghapus file ini? Tindakan ini tidak dapat dibatalkan.</p>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showDeleteModal=false" class="btn-ghost text-black" />
            <x-mary-button label="Hapus" wire:click="confirmDelete" spinner="confirmDelete"
                class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- ─── Verify Confirmation Modal ────────────────────────────── --}}
    <x-mary-modal wire:model="showConfirmModal" title="Konfirmasi Penerimaan Barang" class="backdrop-blur"
        title-class="text-emerald-600">
        <div class="space-y-4">
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-emerald-700">Konfirmasi Penerimaan</p>
                        <p class="text-sm text-emerald-600 mt-1">
                            Anda akan mengonfirmasi bahwa barang pengadaan telah diterima oleh sekolah.
                            Tindakan ini akan mencatat waktu verifikasi dan nama Anda sebagai verifikator.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 text-sm">
                <div class="flex justify-between border-b border-gray-200 pb-2 mb-2">
                    <span class="text-gray-500">Total Foto</span>
                    <span class="font-semibold text-black">{{ $photos->count() }} file</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Dokumen</span>
                    <span class="font-semibold text-black">{{ $documents->count() }} file</span>
                </div>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" @click="$wire.showConfirmModal=false" class="btn-ghost text-black" />
            <x-mary-button label="Ya, Konfirmasi" wire:click="confirmMarkVerified" spinner="confirmMarkVerified"
                class="bg-emerald-500 hover:bg-emerald-600 text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
</div>
