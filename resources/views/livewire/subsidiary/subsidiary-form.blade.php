<div>
    <x-mary-form wire:submit="save">
        <a href="{{ route('superadmin.subsidiaries.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[#0046FF] hover:underline mb-2">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Kembali ke daftar Anak Perusahaan
        </a>

        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $isEdit ? 'Edit Data Anak Perusahaan' : 'Tambah Anak Perusahaan' }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Kelola identitas hukum yang akan digunakan pada transaksi dan dokumen resmi.
                </p>
            </div>
            <x-mary-button label="{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Anak Perusahaan' }}"
                type="submit" icon="o-check"
                class="btn-sm bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none"
                wire:loading.attr="disabled" spinner="save" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                <x-mary-card title="Identitas Wajib" separator shadow>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="Nama Anak Perusahaan" wire:model="name"
                            placeholder="Nama sesuai identitas hukum" :required="true" />
                        <x-mary-input label="NPWP" wire:model="npwp"
                            placeholder="Nomor Pokok Wajib Pajak" :required="true"
                            hint="NPWP harus unik." />
                        <x-mary-input label="Nama Direktur" wire:model="director_name"
                            placeholder="Nama sesuai dokumen resmi" :required="true" />
                    </div>
                </x-mary-card>

                <x-mary-card title="Informasi untuk Dokumen Resmi" separator shadow>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="NIB" wire:model="nib" placeholder="Opsional" />
                        <x-mary-input label="Telepon" wire:model="phone" placeholder="Nomor kontak" />
                        <x-mary-input label="Email" wire:model="email" type="email" placeholder="Email resmi" />
                        <x-mary-input label="Nama narahubung" wire:model="pic_name" placeholder="Opsional" />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat" wire:model="address" rows="3"
                            placeholder="Alamat resmi Anak Perusahaan" />
                    </div>
                </x-mary-card>

                <x-mary-card title="Data Direktur dan Komisaris" separator shadow>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input label="NIK Direktur" wire:model="director_nik" maxlength="16"
                            hint="Opsional; jika diisi harus 16 digit." />
                        <x-mary-input label="NPWP Direktur" wire:model="director_npwp" />
                        <x-mary-input label="Telepon Direktur" wire:model="director_phone" />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat Direktur" wire:model="director_address" rows="2" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                        <x-mary-input label="Nama Komisaris" wire:model="commissioner_name" />
                        <x-mary-input label="NIK Komisaris" wire:model="commissioner_nik" maxlength="16"
                            hint="Opsional; jika diisi harus 16 digit." />
                    </div>
                    <div class="mt-4">
                        <x-mary-textarea label="Alamat Komisaris" wire:model="commissioner_address" rows="2" />
                    </div>
                </x-mary-card>

                @if ($isEdit && $projects->isNotEmpty())
                    <x-mary-card title="Riwayat Transaksi" separator shadow>
                        <div class="overflow-x-auto">
                            <table class="table table-sm w-full text-black">
                                <thead><tr><th>Paket</th><th>Sekolah</th><th>Mulai</th><th>Status</th><th class="text-right">Nilai</th></tr></thead>
                                <tbody>
                                    @foreach ($projects as $project)
                                        <tr>
                                            <td>{{ $project->packageCategory?->name ?? '-' }}</td>
                                            <td>{{ $project->school?->name ?? '-' }}</td>
                                            <td>{{ $project->start_date?->format('d M Y') ?? '-' }}</td>
                                            <td>{{ $project->status_label }}</td>
                                            <td class="text-right">{{ $project->grand_total !== null ? 'Rp '.number_format($project->grand_total, 0, ',', '.') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-mary-card>
                @elseif ($isEdit)
                    <x-mary-card title="Riwayat Transaksi" separator shadow>
                        <p class="text-sm text-gray-500">Belum ada transaksi yang menggunakan Anak Perusahaan ini.</p>
                    </x-mary-card>
                @endif
            </div>

            <div class="space-y-5">
                <x-mary-card title="Pratinjau Kop Surat" separator shadow>
                    <p class="text-xs text-gray-500 mb-4">
                        Kop surat menggunakan komponen tata letak resmi dan data identitas ini secara otomatis.
                    </p>
                    <x-documents.subsidiary-letterhead
                        :name="$name ?: 'Nama Anak Perusahaan'"
                        :npwp="$npwp ?: null"
                        :address="$address"
                        :phone="$phone"
                        :email="$email" />
                </x-mary-card>

                @if ($isEdit && $subsidiary)
                    <x-mary-card shadow>
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="font-bold text-gray-900">Informasi Legalitas</h3>
                                <p class="text-xs text-gray-500 mt-1">Opsional sesuai kebutuhan dokumen.</p>
                            </div>
                            <x-mary-button label="Tambah" icon="o-plus" class="btn-sm"
                                link="{{ route('superadmin.subsidiaries.legal-documents.create', $subsidiary->id) }}"
                                wire:navigate />
                        </div>
                        @forelse ($legalDocuments as $document)
                            <a href="{{ route('superadmin.subsidiaries.legal-documents.edit', [$subsidiary->id, $document->id]) }}"
                                wire:navigate class="flex justify-between gap-3 py-2 border-b border-gray-100 text-sm hover:text-[#0046FF]">
                                <span>{{ $document->document_type->label() }}</span>
                                <span class="font-mono">{{ $document->document_number ?: '-' }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada informasi legalitas tambahan.</p>
                        @endforelse
                    </x-mary-card>

                    <x-mary-card shadow>
                        <h3 class="font-bold text-gray-900">Ringkasan</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Transaksi</dt><dd>{{ $summary['total_projects'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Total nilai</dt><dd>Rp {{ number_format($summary['total_project_value'], 0, ',', '.') }}</dd></div>
                        </dl>
                    </x-mary-card>
                @endif
            </div>
        </div>

        @if ($isEdit && $subsidiary)
            <x-mary-card title="Status Anak Perusahaan" separator shadow class="mt-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-sm text-gray-500">Status saat ini</span>
                    @if ($status === 'active')
                        <x-mary-badge value="Aktif"
                            class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                    @else
                        <x-mary-badge value="Nonaktif"
                            class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                    @endif
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Anak Perusahaan nonaktif tidak dapat dipilih pada transaksi baru. Data dan transaksi historis
                    tetap tersimpan.
                </p>
                @if ($status === 'active')
                    <x-mary-button label="Nonaktifkan" type="button" icon="o-no-symbol"
                        wire:click="confirmDeactivate"
                        class="bg-[#FF8040] hover:bg-[#FF8040]/90 text-white border-none" />
                @else
                    <x-mary-button label="Aktifkan" type="button" icon="o-check-badge"
                        wire:click="activate"
                        class="bg-green-600 hover:bg-green-700 text-white border-none" />
                @endif
            </x-mary-card>
        @endif
    </x-mary-form>

    <x-mary-modal wire:model="confirmingStatusDeactivation" title="Nonaktifkan Anak Perusahaan"
        class="backdrop-blur" title-class="text-[#FF8040]">
        <p class="font-semibold text-black">
            Nonaktifkan <span class="text-[#FF8040]">"{{ $name }}"</span>?
        </p>
        <p class="text-sm text-gray-500 mt-2">
            Anak Perusahaan ini tidak dapat dipilih pada transaksi baru. Transaksi dan dokumen historis tetap tersimpan.
        </p>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingStatusDeactivation', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Nonaktifkan" wire:click="deactivate"
                class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
</div>
