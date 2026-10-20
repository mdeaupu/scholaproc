<div>
    <x-mary-header title="Manajemen Sekolah" subtitle="Daftar sekolah yang terdaftar sebagai pemohon pengadaan barang."
        separator>
        <x-slot:actions>
            <x-mary-button label="Tambah Sekolah" link="{{ route('schools.create') }}" icon="o-plus"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none" wire:navigate />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama atau NPSN..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-56" />
            <x-mary-select wire:model.live="filterStatus" :options="[['id' => 'active', 'name' => 'Aktif'], ['id' => 'suspended', 'name' => 'Dibekukan']]" placeholder="Semua Status" class="w-40" />
        </div>
    </x-mary-card>
    <x-mary-card shadow class="border-t-4 border-[#0046FF]">
        <x-mary-table :headers="$headers" :rows="$schools" with-pagination>
            @scope('cell_npsn', $school)
                <span class="font-mono text-sm font-bold text-black">{{ $school->npsn }}</span>
            @endscope
            @scope('cell_status', $school)
                @if ($school->isActive())
                    <x-mary-badge label="Aktif" class="bg-[#0046FF] text-white border-none badge-sm" />
                @else
                    <x-mary-badge label="Suspended" class="bg-[#FF8040] text-white border-none badge-sm" />
                @endif
            @endscope
            @scope('cell_actions', $school)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-eye" wire:click="show({{ $school->id }})"
                        class="btn-sm btn-ghost text-[#0046FF]" tooltip="Lihat Detail" />
                    <x-mary-button icon="o-pencil-square" link="{{ route('schools.edit', $school->id) }}"
                        class="btn-sm btn-ghost text-black" tooltip="Ubah Data" wire:navigate />
                    @if ($school->isActive())
                        <x-mary-button icon="o-lock-closed"
                            wire:click="confirmSuspend({{ $school->id }}, '{{ addslashes($school->name) }}')"
                            class="btn-sm btn-ghost text-[#FF8040]" tooltip="Bekukan" />
                    @else
                        <x-mary-button icon="o-lock-open" wire:click="activate({{ $school->id }})"
                            class="btn-sm btn-ghost text-[#0046FF]" tooltip="Aktifkan" />
                    @endif
                    <x-mary-button icon="o-trash"
                        wire:click="confirmDestroy({{ $school->id }}, '{{ addslashes($school->name) }}')"
                        class="btn-sm btn-ghost text-[#FF8040]" tooltip="Hapus" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
    <x-mary-modal wire:model="detailModal" title="Profil & Audit Internal Sekolah" class="backdrop-blur text-black"
        box-class="max-w-2xl" title-class="text-[#0046FF]">
        @if ($selectedSchool)
            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Pengajuan</p>
                        <p class="text-3xl font-extrabold text-[#0046FF] font-mono">
                            {{ $selectedSchool->totalRequests() }} <span
                                class="text-base font-medium text-black">Kali</span>
                        </p>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Nilai Pengadaan</p>
                        <p class="text-xl font-extrabold text-black font-mono">
                            Rp {{ number_format($selectedSchool->totalProcurementValue(), 2, ',', '.') }}
                        </p>
                    </div>
                </div>
                @if ($selectedSchool->setting)
                    <div
                        class="border-2 border-dashed border-gray-300 p-4 bg-white text-black font-serif text-center rounded-xl">
                        <p class="text-xs font-bold leading-tight">
                            {{ $selectedSchool->setting->getLetterHead()['pusat'] }}</p>
                        <p class="text-xs font-bold leading-tight">
                            {{ $selectedSchool->setting->getLetterHead()['provinsi'] }}
                        </p>
                        @if ($selectedSchool->setting->getLetterHead()['sub_wilayah'])
                            <p class="text-xs font-bold leading-tight">
                                {{ $selectedSchool->setting->getLetterHead()['sub_wilayah'] }}</p>
                        @endif
                        <p class="text-base font-black tracking-wide my-1">
                            {{ $selectedSchool->setting->getLetterHead()['sekolah'] }}</p>
                        <div class="border-t border-black pt-1 text-[9px] font-sans leading-snug">
                            {{ $selectedSchool->setting->getLetterHead()['alamat_lengkap'] }}<br>
                            {{ $selectedSchool->setting->getLetterHead()['kontak'] }}
                        </div>
                        <div class="border-b-4 border-double border-black mt-1"></div>
                    </div>
                    <p class="text-center text-[11px] text-gray-400 italic -mt-2">Simulasi kop surat pada dokumen PDF
                    </p>
                @endif
                <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm border border-gray-100">
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-500 font-medium">NPSN</span>
                        <span class="font-mono font-bold">{{ $selectedSchool->npsn }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-500 font-medium">No. Telepon</span>
                        <span>{{ $selectedSchool->phone_number }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-500 font-medium">Email Resmi</span>
                        <span>{{ $selectedSchool->email ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-500 font-medium">Kepala Sekolah</span>
                        <span class="text-right">{{ $selectedSchool->principal_name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-500 font-medium">NIP Kepala Sekolah</span>
                        <span class="font-mono text-right">{{ $selectedSchool->principal_nip ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-gray-500 font-medium">Sumber Dana Boleh</span>
                        <span class="text-right text-xs">
                            @if ($selectedSchool->fundingSources->isEmpty())
                                Semua sumber dana aktif
                            @else
                                {{ $selectedSchool->fundingSources->pluck('name')->join(', ') }}
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span class="text-gray-500 font-medium">Status</span>
                        @if ($selectedSchool->isActive())
                            <x-mary-badge label="Aktif" class="bg-[#0046FF] text-white border-none badge-sm" />
                        @else
                            <x-mary-badge label="Dibekukan" class="bg-[#FF8040] text-white border-none badge-sm" />
                        @endif
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 text-sm border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-3">
                        <span class="text-gray-500 font-medium">Akun Admin Sekolah</span>
                        @if ($selectedSchool->account)
                            @if ($selectedSchool->account->status === 'active')
                                <x-mary-badge label="Aktif" class="bg-[#0046FF] text-white border-none badge-sm" />
                            @else
                                <x-mary-badge label="Nonaktif" class="bg-[#FF8040] text-white border-none badge-sm" />
                            @endif
                        @endif
                    </div>
                    @if ($selectedSchool->account)
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-medium">Username</span>
                                <span class="font-mono font-bold">{{ $selectedSchool->account->username }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-medium">Email</span>
                                <span>{{ $selectedSchool->account->email ?? '-' }}</span>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <x-mary-button label="Reset Password" icon="o-key"
                                    wire:click="confirmResetPassword({{ $selectedSchool->id }})"
                                    class="btn-sm bg-[#0046FF] text-white border-none" />
                                @if ($selectedSchool->account->status === 'active')
                                    <x-mary-button label="Nonaktifkan Akun" icon="o-no-symbol"
                                        wire:click="confirmToggleAccount({{ $selectedSchool->id }})"
                                        class="btn-sm bg-[#FF8040] text-white border-none" />
                                @else
                                    <x-mary-button label="Aktifkan Akun" icon="o-check-badge"
                                        wire:click="confirmToggleAccount({{ $selectedSchool->id }})"
                                        class="btn-sm bg-[#0046FF] text-white border-none" />
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">
                            Belum ada akun admin untuk sekolah ini. Isi password saat edit sekolah untuk membuatnya.
                        </p>
                    @endif
                </div>
            </div>
        @endif
        <x-slot:actions>
            <x-mary-button label="Tutup" wire:click="$set('detailModal', false)" class="btn-ghost text-black" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingSuspend" title="Bekukan Instansi Sekolah" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-lock-closed" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Bekukan <span
                        class="text-[#FF8040]">"{{ $targetSchoolName }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Seluruh akses pengguna dari instansi ini akan dikunci dan pengajuan yang sedang berjalan
                    ditangguhkan.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingSuspend', false)" class="btn-ghost text-black" />
            <x-mary-button label="Ya, Bekukan" wire:click="suspend" class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingDestroy" title="Hapus Instansi Sekolah" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-trash" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Hapus <span
                        class="text-[#FF8040]">"{{ $targetSchoolName }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Seluruh data, riwayat audit, dan dokumen pengadaan terkait akan dihapus (Soft Delete) dari sistem.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDestroy', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="destroy" class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingResetPassword" title="Reset Password Akun Sekolah" class="backdrop-blur"
        title-class="text-[#0046FF]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#0046FF]/10 text-[#0046FF] rounded-full shrink-0">
                <x-mary-icon name="o-key" class="w-7 h-7" />
            </div>
            <div class="flex-1">
                <p class="font-semibold text-black">Reset password untuk <span
                        class="text-[#0046FF]">"{{ $targetSchoolName }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Password akun admin sekolah akan diganti dengan nilai baru di bawah.
                </p>
                <x-mary-input label="Password Baru" type="password" wire:model="newPassword"
                    placeholder="Minimal 8 karakter" icon="o-lock-closed" class="mt-4" />
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingResetPassword', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Reset Password" wire:click="resetUserPassword"
                class="bg-[#0046FF] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
    <x-mary-modal wire:model="confirmingToggleAccount" title="Ubah Status Akun Sekolah" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#FF8040]/10 text-[#FF8040] rounded-full shrink-0">
                <x-mary-icon name="o-no-symbol" class="w-7 h-7" />
            </div>
            <div>
                <p class="font-semibold text-black">Ubah status akun <span
                        class="text-[#FF8040]">"{{ $targetAccountName }}"</span>?</p>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Akun nonaktif tidak dapat digunakan untuk login, namun riwayat transaksi tetap tersimpan.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingToggleAccount', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Ubah Status" wire:click="toggleAccount"
                class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
</div>
