<div>
    <x-mary-header title="Manajemen Sekolah"
        subtitle="Kelola data sekolah, status keaktifan, dan konfigurasi sekolah." />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                <x-mary-icon name="o-building-library" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Total Sekolah</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $totalSchools }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    Sekolah aktif: {{ $activeSchools }} | Nonaktif: {{ $suspendedSchools }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <x-mary-icon name="o-check-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Sekolah Aktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $activeSchools }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($percentActive, 1, ',', '.') }}% dari total</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <span class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                <x-mary-icon name="o-x-circle" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-gray-500">Sekolah Nonaktif</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mt-0.5">{{ $suspendedSchools }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($percentSuspended, 1, ',', '.') }}% dari total</p>
            </div>
        </div>
    </div>

    <x-mary-card shadow class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-mary-input placeholder="Cari nama, NPSN, atau alamat..." wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass" clearable class="w-full sm:w-72 lg:w-80" />
            <x-mary-select wire:model.live="filterStatus" :options="[['id' => '', 'name' => 'Semua'], ['id' => 'active', 'name' => 'Aktif'], ['id' => 'suspended', 'name' => 'Nonaktif']]" class="w-40" />
            <x-mary-button label="Tambah Sekolah" link="{{ route('schools.create') }}" icon="o-plus"
                class="bg-[#0046FF] hover:bg-[#0046FF]/90 text-white border-none ml-auto" wire:navigate />
        </div>
    </x-mary-card>
    <x-mary-card title="Daftar Sekolah" separator shadow>
        <x-mary-table :headers="$headers" :rows="$schools" :sort-by="$sortBy" container-class="overflow-visible"
            class="table-fixed w-full">
            @scope('cell_no', $school)
                <span class="text-sm text-gray-400">{{ $school->row_no }}</span>
            @endscope
            @scope('cell_name', $school)
                <div class="flex items-center gap-2.5 min-w-0">
                    <span
                        class="shrink-0 w-7 h-7 rounded-lg bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                        <x-mary-icon name="o-building-library" class="w-4 h-4" />
                    </span>
                    <span class="font-semibold text-gray-900 truncate" title="{{ $school->name }}">{{ $school->name }}</span>
                </div>
            @endscope
            @scope('cell_npsn', $school)
                <span class="font-mono text-sm font-semibold text-gray-700">{{ $school->npsn }}</span>
            @endscope
            @scope('cell_address', $school)
                <span class="block truncate text-sm text-gray-600"
                    title="{{ $school->address }}">{{ $school->address }}</span>
            @endscope
            @scope('cell_status', $school)
                @if ($school->isActive())
                    <x-mary-badge value="Aktif" class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                @else
                    <x-mary-badge value="Nonaktif" class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                @endif
            @endscope
            @scope('cell_created_at', $school)
                <span class="text-sm text-gray-600">{{ optional($school->created_at)->format('d M Y') }}</span>
            @endscope
            @scope('cell_actions', $school)
                <div class="flex justify-end">
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button type="button" class="btn btn-ghost btn-sm btn-square text-gray-400 hover:text-[#0046FF] hover:bg-base-200"
                                title="Menu aksi">
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                            </button>
                        </x-slot:trigger>

                        <x-mary-menu-item title="Detail" icon="o-eye" class="cursor-pointer"
                            wire:click="show({{ $school->id }})" />
                        <x-mary-menu-item title="Edit Data" icon="o-pencil-square" class="cursor-pointer"
                            route="schools.edit" :route-params="[$school->id]" />
                        <x-mary-menu-item title="Hapus (Soft Delete)" icon="o-trash"
                            class="cursor-pointer text-red-500"
                            wire:click="confirmDestroy({{ $school->id }}, '{{ addslashes($school->name) }}')" />
                    </x-mary-dropdown>
                </div>
            @endscope
        </x-mary-table>

        @if ($schools->total() > 0)
            <div>{{ $schools->links('pagination.table') }}</div>
        @endif
    </x-mary-card>
    <x-mary-modal wire:model="detailModal" title="Detail Sekolah"
        subtitle="Informasi lengkap sekolah, akun, dan konfigurasi." class="backdrop-blur text-black"
        box-class="max-w-4xl" title-class="text-[#0046FF]">
        @if ($selectedSchool)
            <div class="space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <div class="flex items-center gap-1.5 text-gray-500 mb-3">
                            <x-mary-icon name="o-building-library" class="w-4 h-4" />
                            <p class="text-xs uppercase font-semibold">Informasi Sekolah</p>
                        </div>
                        <div class="flex items-start gap-3 mb-3">
                            <span
                                class="shrink-0 w-14 h-14 rounded-2xl bg-[#0046FF]/10 text-[#0046FF] flex items-center justify-center">
                                <x-mary-icon name="o-building-library" class="w-7 h-7" />
                            </span>
                            <div class="min-w-0">
                                <p class="font-bold text-base text-gray-900 leading-snug">{{ $selectedSchool->name }}</p>
                                @if ($selectedSchool->isActive())
                                    <x-mary-badge value="Aktif"
                                        class="bg-green-100 text-green-700 border-none badge-sm font-semibold mt-1" />
                                @else
                                    <x-mary-badge value="Nonaktif"
                                        class="bg-red-100 text-red-600 border-none badge-sm font-semibold mt-1" />
                                @endif
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-start gap-2">
                                <span class="text-gray-500 shrink-0 w-28">NPSN</span>
                                <span class="text-gray-400">:</span>
                                <span class="font-mono font-semibold text-gray-800 break-words">{{ $selectedSchool->npsn }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-gray-500 shrink-0 w-28">Alamat</span>
                                <span class="text-gray-400">:</span>
                                <span class="text-gray-800 break-words">{{ $selectedSchool->address }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-gray-500 shrink-0 w-28">Email</span>
                                <span class="text-gray-400">:</span>
                                <span class="text-gray-800 break-words">{{ $selectedSchool->email ?? '-' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-gray-500 shrink-0 w-28">Telepon</span>
                                <span class="text-gray-400">:</span>
                                <span class="text-gray-800 break-words">{{ $selectedSchool->phone_number ?: '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <div class="flex items-center gap-1.5 text-gray-500 mb-3">
                                <x-mary-icon name="o-user" class="w-4 h-4" />
                                <p class="text-xs uppercase font-semibold">Kepala Sekolah</p>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start gap-2">
                                    <span class="text-gray-500 shrink-0 w-28">Nama</span>
                                    <span class="text-gray-400">:</span>
                                    <span class="text-gray-800 break-words">{{ $selectedSchool->principal_name ?? '-' }}</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="text-gray-500 shrink-0 w-28">NIP</span>
                                    <span class="text-gray-400">:</span>
                                    <span class="font-mono text-gray-800 break-words">{{ $selectedSchool->principal_nip ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <div class="flex items-center gap-1.5 text-gray-500 mb-3">
                                <x-mary-icon name="o-calendar" class="w-4 h-4" />
                                <p class="text-xs uppercase font-semibold">Status &amp; Tanggal</p>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start gap-2">
                                    <span class="text-gray-500 shrink-0 w-28">Terdaftar</span>
                                    <span class="text-gray-400">:</span>
                                    <span class="text-gray-800">{{ optional($selectedSchool->created_at)->format('d M Y') }}</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="text-gray-500 shrink-0 w-28">Total Pengajuan</span>
                                    <span class="text-gray-400">:</span>
                                    <span class="text-gray-800 font-semibold">{{ $selectedSchool->totalRequests() }} kali</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="text-gray-500 shrink-0 w-28">Nilai Pengadaan</span>
                                    <span class="text-gray-400">:</span>
                                    <span class="font-mono text-gray-800 break-words">Rp {{ number_format($selectedSchool->totalProcurementValue(), 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 text-gray-500 mb-3">
                        <x-mary-icon name="o-users" class="w-4 h-4" />
                        <p class="text-xs uppercase font-semibold">Akun Admin Sekolah</p>
                    </div>
                    @if ($selectedSchool->account)
                        <div class="space-y-2.5 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Username</span>
                                <span class="font-mono font-bold text-right break-words">{{ $selectedSchool->account->username }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Email</span>
                                <span class="text-right break-words">{{ $selectedSchool->account->email ?? '-' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Role</span>
                                <x-mary-badge value="School" class="bg-blue-100 text-blue-700 border-none badge-sm font-semibold" />
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-gray-500 font-medium shrink-0">Status Akun</span>
                                @if ($selectedSchool->account->status === 'active')
                                    <x-mary-badge value="Aktif" class="bg-green-100 text-green-700 border-none badge-sm font-semibold" />
                                @else
                                    <x-mary-badge value="Nonaktif" class="bg-red-100 text-red-600 border-none badge-sm font-semibold" />
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">
                            Belum ada akun admin untuk sekolah ini. Isi password saat edit sekolah untuk membuatnya.
                        </p>
                    @endif
                </div>

                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 text-gray-500 mb-3">
                        <x-mary-icon name="o-cog-6-tooth" class="w-4 h-4" />
                        <p class="text-xs uppercase font-semibold">Sumber Dana Boleh</p>
                    </div>
                    @if ($selectedSchool->fundingSources->isEmpty())
                        <p class="text-sm text-gray-500">Semua sumber dana aktif — sekolah dapat memilih sumber
                            dana apa saja.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($selectedSchool->fundingSources as $source)
                                <x-mary-badge :value="$source->name"
                                    class="bg-[#0046FF]/10 text-[#0046FF] border-none badge-sm font-semibold" />
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 text-gray-500 mb-3">
                        <x-mary-icon name="o-document-text" class="w-4 h-4" />
                        <p class="text-xs uppercase font-semibold">Kop Surat</p>
                    </div>
                    @if ($selectedSchool->setting)
                        <div
                            class="border-2 border-dashed border-gray-300 p-4 bg-white text-black font-serif text-center rounded-xl max-w-lg mx-auto">
                            <p class="text-xs font-bold leading-tight">
                                {{ $selectedSchool->setting->getLetterHead()['pusat'] }}</p>
                            <p class="text-xs font-bold leading-tight">
                                {{ $selectedSchool->setting->getLetterHead()['provinsi'] }}</p>
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
                        <p class="text-center text-[11px] text-gray-400 italic mt-1.5">Simulasi tampilan kop surat
                            pada dokumen PDF.</p>
                    @else
                        <p class="text-sm text-gray-500">Kop surat belum dikonfigurasi.</p>
                    @endif
                </div>
            </div>
        @endif
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
                    Akun admin sekolah juga akan dinonaktifkan sehingga tidak dapat login.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDestroy', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="destroy" class="bg-[#FF8040] text-white border-none" />
        </x-slot:actions>
    </x-mary-modal>
</div>
