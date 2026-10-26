<div>
    <x-mary-form wire:submit="save">
        <a href="{{ route('superadmin.subsidiaries.edit', $subsidiary->id) }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline mb-2">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Kembali ke Anak Perusahaan
        </a>

        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $isEdit ? 'Edit Informasi Legalitas' : 'Tambah Informasi Legalitas' }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Catat metadata legalitas tambahan untuk {{ $subsidiary->name }} bila diperlukan.
                </p>
            </div>
            <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Legalitas' }}"
                type="submit" icon="o-check"
                class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                wire:loading.attr="disabled" spinner="save" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-xl border border-gray-100 bg-white p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-mary-select label="Jenis Dokumen" :options="$this->documentTypes"
                        wire:model="document_type" placeholder="Pilih jenis dokumen" :required="true" />
                    <x-mary-input label="Nomor Dokumen" wire:model="document_number"
                        placeholder="Nomor dokumen" :required="true" />
                    <x-mary-input type="date" label="Tanggal Dokumen" wire:model="document_date"
                        icon="o-calendar" :required="true" />
                    <x-mary-input label="Pihak Penerbit" wire:model="issuer"
                        placeholder="Instansi penerbit" />
                    <x-mary-input label="Nama Notaris" wire:model="notary_name"
                        placeholder="Jika berlaku" />
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <x-mary-input type="date" label="Berlaku Sampai" wire:model="valid_until"
                                icon="o-calendar-days" :disabled="$noExpiry" />
                        </div>
                        <div class="pb-7">
                            <x-mary-checkbox label="Tanpa masa berlaku" wire:model.live="noExpiry" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <x-mary-card title="Identitas Anak Perusahaan" separator shadow>
                    <p class="font-semibold text-gray-900">{{ $subsidiary->name }}</p>
                    <p class="font-mono text-sm text-gray-600 mt-1">NPWP {{ $subsidiary->npwp }}</p>
                    <p class="text-xs text-gray-500 mt-2">Pencatatan metadata saja; unggahan scan dokumen tidak diperlukan.</p>
                </x-mary-card>
                <x-mary-card title="Riwayat Legalitas" separator shadow>
                    @forelse ($documentHistory as $history)
                        <div class="py-2 border-b border-gray-100 text-sm">
                            <p class="font-medium">{{ $history->document_type->label() }}</p>
                            <p class="text-xs text-gray-500">{{ $history->document_number }} · {{ $history->document_date?->format('d M Y') ?? '-' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada legalitas tambahan.</p>
                    @endforelse
                </x-mary-card>
            </div>
        </div>
    </x-mary-form>
</div>
