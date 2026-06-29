<div>
    <x-mary-header title="{{ $isEdit ? 'Edit Pengajuan' : 'Buat Pengajuan Baru' }}" separator />
    <x-mary-form wire:submit="save">
        <x-mary-card title="Informasi Umum" shadow separator class="border-t-4 border-[#0046FF]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @can('admin-school-only')
                    <x-mary-input label="Asal Sekolah" value="{{ $school_name }}" readonly
                        class="bg-gray-50 cursor-not-allowed" hint="Otomatis terisi sesuai akun sekolah Anda" />
                @endcan
                @if (auth()->user()->isOwner() || auth()->user()->isAdminCv())
                    <x-mary-choices label="Asal Sekolah" wire:model="school_id" :options="$schools" option-label="name"
                        option-value="id" required single />
                @endif
                <x-mary-input label="Kategori Paket" wire:model="package_category"
                    placeholder="Contoh: Alat Tulis Kantor" required />
                <x-mary-input label="Tahun Anggaran" wire:model="budget_year" type="number" required />
                <x-mary-input label="Sumber Dana" wire:model="funding_source" placeholder="Contoh: BOSP Reguler"
                    required />
            </div>
        </x-mary-card>
        <x-mary-card title="Daftar Barang/Jasa" subtitle="Masukkan item yang dibutuhkan" shadow separator
            class="mt-6 border-t-4 border-[#0046FF]">
            @foreach ($items as $index => $item)
                <div
                    class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-4 p-4 border border-gray-200 rounded-xl bg-gray-50">
                    <div class="md:col-span-3">
                        <x-mary-input label="Nama Item" wire:model="items.{{ $index }}.item_name"
                            placeholder="Contoh: Kertas HVS A4" required />
                    </div>
                    <div class="md:col-span-3">
                        <x-mary-input label="Spesifikasi / Merk" wire:model="items.{{ $index }}.specification"
                            placeholder="Contoh: Sinar Dunia 80gr / Asus Vivobook" />
                    </div>
                    <div class="md:col-span-1">
                        <x-mary-input label="Vol" wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                            type="number" min="1" required />
                    </div>
                    <div class="md:col-span-1">
                        <x-mary-input label="Satuan" wire:model="items.{{ $index }}.unit" placeholder="Rim"
                            required />
                    </div>
                    <div class="md:col-span-3">
                        <x-mary-input label="Harga (Est)"
                            wire:model.live.debounce.500ms="items.{{ $index }}.estimated_price" prefix="Rp"
                            type="number" required />
                    </div>
                    <x-mary-button icon="o-trash" wire:click="removeItem({{ $index }})"
                        class="btn-circle bg-[#FF8040] border-none btn-sm text-white hover:bg-[#FF8040]/90 {{ count($items) <= 1 ? 'opacity-40 cursor-not-allowed' : '' }}"
                        tooltip="Hapus Baris" :disabled="count($items) <= 1" />
                </div>
            @endforeach
            <div class="flex justify-between items-center mt-6">
                <x-mary-button label="Tambah Baris" icon="o-plus" wire:click="addItem"
                    class="btn-sm bg-white border border-[#0046FF] text-[#0046FF] hover:bg-[#0046FF]/10" />
                <div class="text-right">
                    <span class="text-sm text-gray-500">Estimasi Subtotal:</span>
                    <div class="text-2xl font-bold text-[#0046FF]">
                        Rp {{ number_format($this->estimatedSubtotal, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </x-mary-card>
        <div class="flex justify-end gap-3 pt-4">
            <x-mary-button label="Batal" wire:click="cancel" class="btn-ghost btn-sm text-black" />
            <x-mary-button label="Simpan Draft" type="submit" icon="o-server" spinner="save"
                class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" />
        </div>
    </x-mary-form>
</div>
