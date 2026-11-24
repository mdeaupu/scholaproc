<div>
    <x-mary-form wire:submit="save" class="space-y-5" novalidate>
        <a href="{{ route('procurement.index') }}" wire:navigate
            class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline">
            <x-mary-icon name="o-arrow-left" class="h-4 w-4" />
            Kembali ke Daftar Pengajuan
        </a>
        <x-mary-header :title="$isEdit ? 'Edit Pengajuan' : 'Ajukan Pengadaan'"
            subtitle="Lengkapi informasi dan barang yang dibutuhkan. Simpan sebagai draf atau kirim untuk diverifikasi.">
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <x-mary-button label="Simpan Draf" icon="o-document-arrow-down" type="submit"
                        wire:loading.attr="disabled" spinner="save"
                        class="btn-sm border border-[#0046FF] bg-white text-[#0046FF] hover:bg-blue-50" />
                    <x-mary-button :label="$isEdit &&
                    $procurement?->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION
                        ? 'Kirim Revisi'
                        : 'Kirim Pengajuan'" icon="o-paper-airplane" type="button" wire:click="submitRequest"
                        wire:loading.attr="disabled" spinner="submitRequest"
                        class="btn-sm border-none bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
                </div>
            </x-slot:actions>
        </x-mary-header>

        @if ($isEdit && $procurement?->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION)
            <div
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <x-mary-icon name="o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-semibold">Pengajuan perlu diperbaiki</p>
                    <p class="mt-1">Perbarui data sesuai catatan verifikasi, lalu kirim kembali untuk ditinjau.</p>
                </div>
            </div>
        @endif

        <ol class="grid grid-cols-2 gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm lg:grid-cols-4">
            @foreach (['Informasi Pengajuan', 'Detail Barang', 'Ringkasan', 'Konfirmasi'] as $step => $label)
                <li class="flex items-center gap-3">
                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                        'bg-[#0046FF] text-white' => $step < 2,
                        'bg-gray-100 text-gray-500' => $step >= 2,
                    ])>{{ $step + 1 }}</span>
                    <div class="min-w-0">
                        <p @class([
                            'truncate text-sm font-semibold',
                            'text-[#0046FF]' => $step < 2,
                            'text-gray-500' => $step >= 2,
                        ])>{{ $label }}</p>
                        @if ($step === 0)
                            <p class="hidden text-xs text-gray-400 sm:block">Kategori, sumber dana, dan tahun anggaran
                            </p>
                        @elseif ($step === 1)
                            <p class="hidden text-xs text-gray-400 sm:block">Nama, spesifikasi, satuan, dan jumlah</p>
                        @elseif ($step === 2)
                            <p class="hidden text-xs text-gray-400 sm:block">Periksa kembali data pengajuan</p>
                        @else
                            <p class="hidden text-xs text-gray-400 sm:block">Kirim untuk diverifikasi</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-12">
            <div class="space-y-5 xl:col-span-8">
                <x-mary-card title="Informasi Pengajuan" separator shadow>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-mary-select label="Kategori Pengadaan" wire:model.live="package_category_id" :options="$packageCategories
                            ->map(fn($category) => ['id' => $category->id, 'name' => $category->name])
                            ->all()"
                            placeholder="Pilih kategori pengadaan" required />
                        <x-mary-select label="Sumber Dana" wire:model.live="funding_source_id" :options="$fundingSources
                            ->map(fn($source) => ['id' => $source->id, 'name' => $source->name])
                            ->all()"
                            placeholder="Pilih sumber dana" required />
                        <x-mary-select label="Tahun Anggaran" wire:model.live="budget_year_id" :options="$budgetYears->map(fn($year) => ['id' => $year->id, 'name' => $year->name])->all()"
                            placeholder="Pilih tahun anggaran" required />
                        <x-mary-input label="Tanggal Pengajuan" :value="$requestDate->locale('id')->isoFormat('D MMM YYYY')" icon="o-calendar-days" readonly
                            class="bg-gray-50" />
                    </div>
                    @if ($fundingSources->isEmpty())
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            Belum ada sumber dana aktif yang ditetapkan untuk sekolah Anda. Hubungi administrator.
                        </div>
                    @endif
                </x-mary-card>

                <x-mary-card title="Detail Barang" subtitle="Masukkan barang yang diperlukan beserta spesifikasinya."
                    separator shadow>
                    <x-slot:menu>
                        <x-mary-button label="Tambah Barang" icon="o-plus" wire:click="addItem"
                            class="btn-sm bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
                    </x-slot:menu>

                    <div class="space-y-4">
                        @foreach ($items as $index => $item)
                            <div wire:key="procurement-item-{{ $item['key'] }}"
                                class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-sm font-semibold text-gray-700">Barang {{ $index + 1 }}</p>
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        @disabled(count($items) <= 1)
                                        class="btn btn-ghost btn-xs text-red-500 disabled:cursor-not-allowed disabled:opacity-40"
                                        title="Hapus barang">
                                        <x-mary-icon name="o-trash" class="h-4 w-4" />
                                        Hapus
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                    <div class="md:col-span-5">
                                        <x-mary-input label="Nama Barang"
                                            wire:model="items.{{ $index }}.item_name"
                                            placeholder="Contoh: Kertas HVS A4" required />
                                    </div>
                                    <div class="md:col-span-7">
                                        <x-mary-input label="Spesifikasi"
                                            wire:model="items.{{ $index }}.specification"
                                            placeholder="Ukuran, merek, tipe, atau keterangan lain" required />
                                    </div>
                                    <div class="md:col-span-3">
                                        <x-mary-input label="Jumlah"
                                            wire:model.live.debounce.300ms="items.{{ $index }}.quantity"
                                            type="number" min="1" step="1" placeholder="1" required />
                                    </div>
                                    <div class="md:col-span-3">
                                        <x-mary-select label="Satuan" wire:model.live="items.{{ $index }}.unit_id"
                                            :options="$itemUnits
                                                ->map(fn($unit) => ['id' => $unit->id, 'name' => $unit->name])
                                                ->all()" placeholder="Pilih satuan" required />
                                    </div>
                                    <div class="md:col-span-6">
                                        <x-mary-input label="Estimasi Harga Satuan"
                                            wire:model.live.debounce.300ms="items.{{ $index }}.estimated_price"
                                            type="number" min="0" step="0.01" prefix="Rp"
                                            placeholder="Opsional" />
                                    </div>
                                </div>
                                @error("items.$index.item_name")
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error("items.$index.specification")
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error("items.$index.quantity")
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error("items.$index.unit_id")
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error("items.$index.estimated_price")
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <p class="text-xs text-gray-500">Estimasi harga bersifat perkiraan dan dapat dibahas saat
                            verifikasi.</p>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Estimasi</p>
                            <p class="text-xl font-extrabold text-[#0046FF]">
                                Rp {{ number_format($this->estimatedSubtotal, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </x-mary-card>

            </div>

            <aside class="xl:col-span-4">
                <x-mary-card title="Ringkasan Pengajuan" separator shadow class="xl:sticky xl:top-5">
                    <div class="mb-4 flex items-center gap-4 rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                        <span
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[#0046FF]">
                            <x-mary-icon name="o-document-text" class="h-6 w-6" />
                        </span>
                        <div class="grid min-w-0 flex-1 grid-cols-2 divide-x divide-blue-100">
                            <div class="px-2">
                                <p class="text-xs text-gray-500">Total Item</p>
                                <p class="mt-1 font-bold text-gray-900">{{ $this->totalItems }} barang</p>
                            </div>
                            <div class="px-3">
                                <p class="text-xs text-gray-500">Total Estimasi</p>
                                <p class="mt-1 font-bold text-[#0046FF]">Rp
                                    {{ number_format($this->estimatedSubtotal, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 py-4">
                        <div class="mb-3 flex items-center gap-2 text-[#0046FF]">
                            <x-mary-icon name="o-building-library" class="h-5 w-5" />
                            <p class="text-sm font-bold text-gray-900">Informasi Sekolah</p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">Nama Sekolah</dt>
                                <dd class="font-medium text-gray-700">{{ $school->name }}</dd>
                            </div>
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">NPSN</dt>
                                <dd class="font-medium text-gray-700">{{ $school->npsn }}</dd>
                            </div>
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">Alamat</dt>
                                <dd class="text-gray-700">{{ $school->address }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="border-t border-gray-100 py-4">
                        <div class="mb-3 flex items-center gap-2 text-[#0046FF]">
                            <x-mary-icon name="o-clipboard-document-list" class="h-5 w-5" />
                            <p class="text-sm font-bold text-gray-900">Informasi Pengajuan</p>
                        </div>
                        <dl class="space-y-2 text-xs">
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">Kategori</dt>
                                <dd class="font-medium text-gray-700">
                                    {{ $packageCategories->firstWhere('id', (int) $package_category_id)?->name ?? 'Belum dipilih' }}
                                </dd>
                            </div>
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">Sumber Dana</dt>
                                <dd class="font-medium text-gray-700">
                                    {{ $fundingSources->firstWhere('id', (int) $funding_source_id)?->name ?? 'Belum dipilih' }}
                                </dd>
                            </div>
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">Tahun Anggaran</dt>
                                <dd class="font-medium text-gray-700">
                                    {{ $budgetYears->firstWhere('id', (int) $budget_year_id)?->name ?? 'Belum dipilih' }}
                                </dd>
                            </div>
                            <div class="grid grid-cols-[90px_1fr] gap-2">
                                <dt class="text-gray-500">Tanggal</dt>
                                <dd class="font-medium text-gray-700">
                                    {{ $requestDate->locale('id')->isoFormat('D MMM YYYY') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs text-blue-800">
                        <div class="mb-2 flex items-center gap-2 font-bold">
                            <x-mary-icon name="o-information-circle" class="h-4 w-4" />
                            Perlu Diperhatikan
                        </div>
                        <ul class="list-disc space-y-1 pl-5 leading-relaxed">
                            <li>Pastikan data dan jumlah barang sudah benar sebelum dikirim.</li>
                            <li>Pengajuan yang dikirim akan diverifikasi oleh Admin CV.</li>
                            <li>Status dapat dipantau melalui menu Daftar Pengajuan.</li>
                        </ul>
                    </div>
                </x-mary-card>
            </aside>
        </div>
    </x-mary-form>
</div>
