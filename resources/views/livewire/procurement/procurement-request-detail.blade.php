<div class="space-y-5">
    @php
        $request = $procurementRequest;
        $school = $request->school;
        $schoolAccount = $school?->account;
        $requestNumber =
            'PGN-' . $request->created_at->format('Y') . '-' . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT);
        $estimatedTotal = $request->items->sum(fn($item) => $item->estimatedAmount());
        $user = auth()->user();
        $isSchool = $user->isSchool();
        $isInternal = $user->isAdmin() || $user->isSuperAdmin();
        $canEdit =
            $isSchool &&
            in_array(
                $request->status,
                [\App\Models\ProcurementRequest::STATUS_DRAFT, \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION],
                true,
            );
        $hasSchoolActions =
            $isSchool && ($canEdit || $request->status === \App\Models\ProcurementRequest::STATUS_DRAFT);
        $statusLabel = $request->status_label;
        $latestRevision = $request->histories
            ->where('status', \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION)
            ->sortByDesc('created_at')
            ->first();
        $latestRejection = $request->histories
            ->where('status', \App\Models\ProcurementRequest::STATUS_REJECTED)
            ->sortByDesc('created_at')
            ->first();

        $tabs = [
            ['key' => 'informasi', 'label' => 'Informasi & Barang', 'icon' => 'o-information-circle'],
            ['key' => 'negosiasi', 'label' => 'Ruang Negosiasi', 'icon' => 'o-chat-bubble-left-right'],
            ['key' => 'pengiriman', 'label' => 'Pengiriman & Penerimaan', 'icon' => 'o-truck'],
            ['key' => 'dokumen', 'label' => 'Dokumen', 'icon' => 'o-document-text'],
            ['key' => 'riwayat', 'label' => 'Riwayat Aktivitas', 'icon' => 'o-clock'],
        ];

        $schoolHasPendingOffer =
            $isSchool &&
            $request->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION &&
            $request->items->contains(
                fn($item) => !$item->isNegotiationSettled() &&
                    $item->nextOfferBy() === \App\Models\ProcurementNegotiation::OFFERED_BY_SCHOOL,
            );

        $adminHasPendingOffer =
            $isInternal &&
            $request->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION &&
            $request->hasSubsidiary() &&
            $request->items->contains(
                fn($item) => !$item->isNegotiationSettled() &&
                    $item->nextOfferBy() === \App\Models\ProcurementNegotiation::OFFERED_BY_ADMIN,
            );

        $isNegotiationDone = in_array(
            $request->status,
            [
                \App\Models\ProcurementRequest::STATUS_PRICE_AGREED,
                \App\Models\ProcurementRequest::STATUS_PROCESSING,
                \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                \App\Models\ProcurementRequest::STATUS_DISPUTED,
                \App\Models\ProcurementRequest::STATUS_RECEIVED,
                \App\Models\ProcurementRequest::STATUS_INVOICED,
                \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
                \App\Models\ProcurementRequest::STATUS_COMPLETED,
            ],
            true,
        );

        // Titik indikator tab: 'action' (oranye nyala-mati = giliran Anda), 'waiting' (biru diam = sedang berjalan/menunggu)
        $tabIndicators = [
            'informasi' => match (true) {
                ($isSchool &&
                    in_array(
                        $request->status,
                        [
                            \App\Models\ProcurementRequest::STATUS_DRAFT,
                            \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION,
                        ],
                        true,
                    )) ||
                    ($isInternal && $request->status === \App\Models\ProcurementRequest::STATUS_SUBMITTED)
                    => 'action',
                ($isSchool && $request->status === \App\Models\ProcurementRequest::STATUS_SUBMITTED) ||
                    ($isInternal &&
                        in_array(
                            $request->status,
                            [
                                \App\Models\ProcurementRequest::STATUS_DRAFT,
                                \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION,
                            ],
                            true,
                        ))
                    => 'waiting',
                default => null,
            },
            'negosiasi' => match (true) {
                $schoolHasPendingOffer || $adminHasPendingOffer => 'action',
                ($isSchool && $request->status === \App\Models\ProcurementRequest::STATUS_VERIFIED) ||
                    $request->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION
                    => 'waiting',
                default => null,
            },
            'pengiriman' => match (true) {
                ($isInternal &&
                    in_array(
                        $request->status,
                        [
                            \App\Models\ProcurementRequest::STATUS_PRICE_AGREED,
                            \App\Models\ProcurementRequest::STATUS_PROCESSING,
                            \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                            \App\Models\ProcurementRequest::STATUS_DISPUTED,
                            \App\Models\ProcurementRequest::STATUS_RECEIVED,
                            \App\Models\ProcurementRequest::STATUS_INVOICED,
                            \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
                        ],
                        true,
                    )) ||
                    ($isSchool &&
                        in_array(
                            $request->status,
                            [
                                \App\Models\ProcurementRequest::STATUS_PROCESSING,
                                \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                                \App\Models\ProcurementRequest::STATUS_DISPUTED,
                            ],
                            true,
                        ))
                    => 'action',
                $isSchool &&
                    in_array(
                        $request->status,
                        [
                            \App\Models\ProcurementRequest::STATUS_PRICE_AGREED,
                            \App\Models\ProcurementRequest::STATUS_RECEIVED,
                            \App\Models\ProcurementRequest::STATUS_INVOICED,
                            \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
                        ],
                        true,
                    )
                    => 'waiting',
                default => null,
            },
            'dokumen' => $request->status === \App\Models\ProcurementRequest::STATUS_COMPLETED ? 'waiting' : null,
            'riwayat' => null,
        ];

        // Catatan kontekstual di dalam masing-masing tab sesuai kejadian yang berlangsung
        $tabNotices = [
            'informasi' => match (true) {
                $request->status === \App\Models\ProcurementRequest::STATUS_DRAFT => $isSchool
                    ? [
                        'type' => 'action',
                        'title' => 'Pengajuan Masih Berstatus Draf',
                        'desc' =>
                            'Periksa kembali rincian barang di bawah. Jika sudah sesuai, klik tombol "Kirim Pengajuan" di bagian atas agar dapat diverifikasi Admin.',
                    ]
                    : [
                        'type' => 'waiting',
                        'title' => 'Menunggu Sekolah Mengirimkan Pengajuan',
                        'desc' =>
                            'Pengajuan ini masih berupa draf di akun sekolah dan belum dikirimkan untuk verifikasi.',
                    ],
                $request->status === \App\Models\ProcurementRequest::STATUS_SUBMITTED => $isSchool
                    ? [
                        'type' => 'waiting',
                        'title' => 'Menunggu Verifikasi Admin',
                        'desc' =>
                            'Pengajuan Anda telah terkirim dan sedang diperiksa kelengkapannya oleh Admin. Anda belum perlu melakukan tindakan apa pun saat ini.',
                    ]
                    : [
                        'type' => 'action',
                        'title' => 'Menunggu Verifikasi Anda',
                        'desc' =>
                            'Periksa kelengkapan identitas sekolah dan daftar barang di bawah, lalu pilih Verifikasi, Minta Revisi, atau Tolak pada panel atas.',
                    ],
                $request->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION => $isSchool
                    ? [
                        'type' => 'action',
                        'title' => 'Perlu Perbaikan Data Pengajuan',
                        'desc' =>
                            'Admin meminta revisi pada pengajuan ini. Klik tombol "Revisi Pengajuan" di bagian atas untuk memperbarui data.',
                    ]
                    : [
                        'type' => 'waiting',
                        'title' => 'Menunggu Perbaikan dari Sekolah',
                        'desc' =>
                            'Catatan revisi telah dikirim. Saat ini menunggu pihak sekolah memperbarui data dan mengirim ulang pengajuan.',
                    ],
                $request->status === \App\Models\ProcurementRequest::STATUS_VERIFIED => $isSchool
                    ? [
                        'type' => 'done',
                        'title' => 'Data Pengajuan Telah Terverifikasi',
                        'desc' =>
                            'Informasi dan daftar barang sudah disetujui Admin. Saat ini menunggu Admin menetapkan penyedia dan membuka Ruang Negosiasi.',
                    ]
                    : [
                        'type' => 'done',
                        'title' => 'Pengajuan Terverifikasi',
                        'desc' =>
                            'Data pengajuan sudah valid. Silakan tetapkan Anak Perusahaan pada panel atas lalu klik "Teruskan ke Negosiasi".',
                    ],
                $request->status === \App\Models\ProcurementRequest::STATUS_REJECTED => null,
                default => [
                    'type' => 'done',
                    'title' => 'Data Pengajuan Terkunci',
                    'desc' =>
                        'Pengajuan telah melewati tahap verifikasi. Pantau kelanjutan proses pada tab berikutnya.',
                ],
            },

            'negosiasi' => match (true) {
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_DRAFT,
                        \App\Models\ProcurementRequest::STATUS_SUBMITTED,
                        \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION,
                    ],
                    true,
                )
                    => [
                    'type' => 'waiting',
                    'title' => 'Ruang Negosiasi Belum Dibuka',
                    'desc' =>
                        'Negosiasi harga per item baru dapat dilakukan setelah pengajuan dikirim dan diverifikasi oleh Admin.',
                ],
                $request->status === \App\Models\ProcurementRequest::STATUS_VERIFIED => $isSchool
                    ? [
                        'type' => 'waiting',
                        'title' => 'Menunggu Admin Membuka Sesi Negosiasi',
                        'desc' =>
                            'Pengajuan Anda sudah terverifikasi. Menunggu Admin menetapkan Anak Perusahaan penyedia dan meneruskan pengajuan ke tahap negosiasi.',
                    ]
                    : [
                        'type' => 'action',
                        'title' => 'Siap Diteruskan ke Negosiasi',
                        'desc' =>
                            'Pilih Anak Perusahaan pada panel atas, kemudian klik "Teruskan ke Negosiasi" untuk mulai mengajukan penawaran harga.',
                    ],
                $request->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION && !$request->hasSubsidiary()
                    => [
                    'type' => 'waiting',
                    'title' => 'Menunggu Penetapan Anak Perusahaan',
                    'desc' =>
                        'Anak Perusahaan penyedia wajib ditetapkan pada panel atas sebelum penawaran harga dapat dikirimkan.',
                ],
                $schoolHasPendingOffer || $adminHasPendingOffer => [
                    'type' => 'action',
                    'title' => 'Giliran Anda Menanggapi Harga',
                    'desc' => $isSchool
                        ? 'Admin telah mengirimkan penawaran harga. Pilih barang di bawah untuk menyetujui atau mengajukan penawaran balik.'
                        : 'Sekolah menunggu penawaran harga dari Anda. Pilih barang di bawah untuk mengirim atau menanggapi penawaran.',
                ],
                $request->status === \App\Models\ProcurementRequest::STATUS_NEGOTIATION => [
                    'type' => 'waiting',
                    'title' => $isSchool
                        ? 'Menunggu Penawaran Harga dari Admin'
                        : 'Menunggu Tanggapan Harga dari Sekolah',
                    'desc' => $isSchool
                        ? 'Penawaran telah dikirim. Menunggu Admin mengajukan atau membalas harga untuk item yang masih dinegosiasikan.'
                        : 'Penawaran harga telah dikirimkan ke sekolah. Menunggu pihak sekolah menyetujui atau mengajukan penawaran balik.',
                ],
                $isNegotiationDone => [
                    'type' => 'done',
                    'title' => 'Negosiasi Harga Selesai',
                    'desc' =>
                        'Harga resmi seluruh barang telah disepakati. Proses berlanjut ke tab Pengiriman & Penerimaan.',
                ],
                default => null,
            },

            'pengiriman' => match (true) {
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_DRAFT,
                        \App\Models\ProcurementRequest::STATUS_SUBMITTED,
                        \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION,
                        \App\Models\ProcurementRequest::STATUS_VERIFIED,
                        \App\Models\ProcurementRequest::STATUS_NEGOTIATION,
                    ],
                    true,
                )
                    => [
                    'type' => 'waiting',
                    'title' => 'Tahap Pengiriman Belum Dimulai',
                    'desc' =>
                        'Pengiriman dan pencatatan penerimaan barang akan terbuka setelah harga seluruh barang disepakati di Ruang Negosiasi.',
                ],
                $request->status === \App\Models\ProcurementRequest::STATUS_PRICE_AGREED => $isSchool
                    ? [
                        'type' => 'waiting',
                        'title' => 'Menunggu Persiapan & Pengiriman dari Admin',
                        'desc' =>
                            'Harga telah disepakati. Menunggu Admin menerbitkan Surat Pesanan dan menjadwalkan pengiriman barang ke sekolah.',
                    ]
                    : [
                        'type' => 'action',
                        'title' => 'Siap Diproses & Dikirim',
                        'desc' =>
                            'Harga telah disepakati. Klik "Mulai Pemrosesan & Terbitkan Surat Pesanan" di bawah untuk memulai tahap pengiriman.',
                    ],
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_PROCESSING,
                        \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                        \App\Models\ProcurementRequest::STATUS_DISPUTED,
                    ],
                    true,
                )
                    => $isSchool
                    ? [
                        'type' => 'action',
                        'title' => 'Barang Dalam Proses Pengiriman',
                        'desc' =>
                            'Periksa daftar barang yang dikirim. Jika barang sudah tiba di sekolah, catat jumlah penerimaan dan unggah bukti foto/dokumen bertanda tangan di bawah.',
                    ]
                    : [
                        'type' => 'action',
                        'title' => 'Pengiriman & Verifikasi Penerimaan Berjalan',
                        'desc' =>
                            'Catat pengiriman barang ke sekolah atau verifikasi draf penerimaan barang yang sudah diinput oleh pihak sekolah di bawah.',
                    ],
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_RECEIVED,
                        \App\Models\ProcurementRequest::STATUS_INVOICED,
                        \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
                    ],
                    true,
                )
                    => $isSchool
                    ? [
                        'type' => 'waiting',
                        'title' => 'Barang Diterima · Menunggu Penyelesaian Pembayaran',
                        'desc' =>
                            'Penerimaan barang telah terverifikasi. Saat ini Admin sedang memproses penerbitan invoice dan penyelesaian pembayaran.',
                    ]
                    : [
                        'type' => 'action',
                        'title' => 'Menunggu Proses Invoice & Pembayaran',
                        'desc' =>
                            'Barang sudah diterima sekolah. Silakan proses invoice, catat pembayaran, dan tandai pengadaan selesai pada bagian bawah panel ini.',
                    ],
                $request->status === \App\Models\ProcurementRequest::STATUS_COMPLETED => [
                    'type' => 'done',
                    'title' => 'Pengiriman, Penerimaan & Pembayaran Selesai',
                    'desc' => 'Seluruh barang telah diterima dengan baik dan administrasi pembayaran telah tuntas.',
                ],
                default => null,
            },

            'dokumen' => match (true) {
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_DRAFT,
                        \App\Models\ProcurementRequest::STATUS_SUBMITTED,
                        \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION,
                        \App\Models\ProcurementRequest::STATUS_VERIFIED,
                        \App\Models\ProcurementRequest::STATUS_NEGOTIATION,
                    ],
                    true,
                )
                    => [
                    'type' => 'waiting',
                    'title' => 'Menunggu Tahap Kesepakatan Harga',
                    'desc' =>
                        'Dokumen resmi pengadaan (Surat Pesanan, Surat Jalan, BAST, Invoice, dan Kuitansi) akan diterbitkan secara bertahap setelah harga disepakati.',
                ],
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_PRICE_AGREED,
                        \App\Models\ProcurementRequest::STATUS_PROCESSING,
                        \App\Models\ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                        \App\Models\ProcurementRequest::STATUS_DISPUTED,
                    ],
                    true,
                )
                    => [
                    'type' => 'waiting',
                    'title' => 'Sebagian Dokumen Menunggu Penerimaan Barang',
                    'desc' =>
                        'Dokumen tahap awal sudah dapat diunduh jika tersedia. BAST, Invoice, dan Kuitansi akan terbit setelah barang diterima dan diverifikasi.',
                ],
                in_array(
                    $request->status,
                    [
                        \App\Models\ProcurementRequest::STATUS_RECEIVED,
                        \App\Models\ProcurementRequest::STATUS_INVOICED,
                        \App\Models\ProcurementRequest::STATUS_PAYMENT_PENDING,
                    ],
                    true,
                )
                    => [
                    'type' => 'waiting',
                    'title' => 'Menunggu Penerbitan Dokumen Pembayaran',
                    'desc' =>
                        'BAST tersedia setelah terbit. Invoice dan Kuitansi akan muncul pada tabel di bawah setelah penagihan dan pembayaran dikonfirmasi Admin.',
                ],
                $request->status === \App\Models\ProcurementRequest::STATUS_COMPLETED => [
                    'type' => 'done',
                    'title' => 'Seluruh Dokumen Pengadaan Tersedia',
                    'desc' => 'Semua berkas resmi pengadaan telah lengkap dan dapat diunduh pada tabel di bawah.',
                ],
                default => null,
            },
        ];

        $renderTabNotice = function (?array $notice) {
            if (!$notice) {
                return '';
            }
            $styles = match ($notice['type']) {
                'action' => [
                    'wrap' => 'border-blue-200 bg-blue-50/70 text-slate-800',
                    'iconBg' => 'bg-[#0046FF] text-white',
                    'icon' => 'o-arrow-right-circle',
                ],
                'done' => [
                    'wrap' => 'border-emerald-200 bg-emerald-50/70 text-emerald-950',
                    'iconBg' => 'bg-emerald-600 text-white',
                    'icon' => 'o-check-circle',
                ],
                default => [
                    'wrap' => 'border-slate-200 bg-slate-50 text-slate-700',
                    'iconBg' => 'bg-white text-[#0046FF] border border-slate-200',
                    'icon' => 'o-clock',
                ],
            };

            return view('components.procurement-tab-notice', compact('notice', 'styles'));
        };
    @endphp

    <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('procurement.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 font-medium text-[#0046FF] hover:underline">
            <x-mary-icon name="o-arrow-left" class="h-4 w-4" />
            Kembali ke Daftar Pengajuan
        </a>
    </nav>

    <main class="min-w-0 space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Detail Pengajuan Pengadaan Barang</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-mono font-semibold text-gray-600">#{{ $requestNumber }}</span>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                        {{ $school?->name ?? 'Sekolah' }}
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $request->status_pill_class }}">
                    <x-mary-icon name="o-information-circle" class="h-4 w-4" />
                    {{ $statusLabel }}
                </span>
                <span
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600">
                    <x-mary-icon name="o-calendar-days" class="h-4 w-4 text-[#0046FF]" />
                    {{ $request->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm') }}
                </span>
            </div>
        </header>

        @if ($request->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION && $latestRevision)
            <div
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <x-mary-icon name="o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-bold">Revisi diperlukan</p>
                    <p class="mt-1">{{ \Illuminate\Support\Str::after($latestRevision->notes, 'Revisi diminta: ') }}
                    </p>
                    <p class="mt-2 text-xs text-amber-700">
                        {{ $latestRevision->createdBy?->name ?? 'Admin' }} ·
                        {{ $latestRevision->created_at?->locale('id')->isoFormat('D MMM YYYY HH:mm') }}
                    </p>
                </div>
            </div>
        @endif

        @if ($request->status === \App\Models\ProcurementRequest::STATUS_REJECTED)
            <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                <x-mary-icon name="o-x-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-bold">Pengajuan ditolak</p>
                    <p class="mt-1">{{ $latestRejection?->notes ?? 'Alasan penolakan tidak tersedia.' }}</p>
                    @if ($latestRejection)
                        <p class="mt-2 text-xs text-red-700">
                            {{ $latestRejection->createdBy?->name ?? 'Admin' }} ·
                            {{ $latestRejection->created_at?->locale('id')->isoFormat('D MMM YYYY HH:mm') }}
                        </p>
                    @endif
                </div>
            </div>
        @endif

        @if ($hasSchoolActions)
            <section
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                <div>
                    <p class="text-sm font-bold text-gray-900">Tindakan Diperlukan</p>
                    <p class="text-xs text-gray-600">Lengkapi, ubah, atau kirim pengajuan ini untuk diproses.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canEdit)
                        <x-mary-button :label="$request->status === \App\Models\ProcurementRequest::STATUS_NEEDS_REVISION
                            ? 'Revisi Pengajuan'
                            : 'Edit Pengajuan'" icon="o-pencil-square"
                            link="{{ route('procurement.edit', $request->id) }}" wire:navigate
                            class="btn-sm border-[#0046FF] bg-white text-[#0046FF] hover:bg-blue-50" />
                    @endif
                    @if ($request->status === \App\Models\ProcurementRequest::STATUS_DRAFT)
                        <x-mary-button label="Hapus Draf" icon="o-trash" wire:click="confirmDeleteDraft"
                            class="btn-sm border border-red-200 bg-white text-red-600 hover:bg-red-50" />
                        <x-mary-button label="Kirim Pengajuan" icon="o-paper-airplane" wire:click="submitRequest"
                            wire:confirm="Kirim pengajuan ini untuk diverifikasi?" wire:loading.attr="disabled"
                            spinner="submitRequest" class="btn-sm bg-[#0046FF] text-white hover:bg-[#0046FF]/90" />
                    @endif
                </div>
            </section>
        @elseif ($isInternal)
            <livewire:procurement.procurement-review-panel :procurement-request="$request"
                wire:key="review-panel-{{ $request->id }}" />
        @endif

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-5" aria-label="Ringkasan pengajuan">
            <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-700 shadow-sm">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#0046FF]">
                        <x-mary-icon name="o-building-library" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">Sekolah</p>
                        <p class="truncate font-semibold text-gray-900">{{ $school?->name ?? '-' }}</p>
                        <p class="text-xs text-gray-400">NPSN: {{ $school?->npsn ?? '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-700 shadow-sm">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-600">
                        <x-mary-icon name="o-banknotes" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">Sumber Dana</p>
                        <p class="truncate font-semibold text-gray-900">{{ $request->fundingSource?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-700 shadow-sm">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                        <x-mary-icon name="o-calendar-days" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">Tahun Anggaran</p>
                        <p class="truncate font-semibold text-gray-900">{{ $request->budgetYear?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-700 shadow-sm">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                        <x-mary-icon name="o-tag" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">Kategori</p>
                        <p class="truncate font-semibold text-gray-900">{{ $request->packageCategory?->name ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="col-span-2 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-gray-700 shadow-sm xl:col-span-1">
                <p class="text-xs font-medium text-[#0046FF]">Total Nilai Pengajuan</p>
                <p class="mt-1 text-lg font-extrabold text-gray-900">Rp
                    {{ number_format($estimatedTotal, 0, ',', '.') }}</p>
            </div>
        </section>

        {{-- Navigasi Tab: Ikon asli + Titik Oranye Berdenyut (Giliran Anda) & Titik Biru Diam (Sedang Menunggu) --}}
        <nav class="flex overflow-x-auto border-b border-gray-200 bg-white" aria-label="Menu detail pengajuan">
            @foreach ($tabs as $tab)
                @php
                    $indicator = $tabIndicators[$tab['key']] ?? null;
                    $isActive = $activeTab === $tab['key'];
                @endphp
                <button type="button" wire:click="setActiveTab('{{ $tab['key'] }}')"
                    @if ($isActive) aria-current="page" @endif
                    class="flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm transition-colors
                        {{ $isActive
                            ? 'text-blue-600 border-b-2 border-blue-600 font-semibold'
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <x-mary-icon :name="$tab['icon']" class="h-5 w-5" />
                    <span>{{ $tab['label'] }}</span>

                    @if ($indicator === 'action')
                        <span class="relative flex h-2.5 w-2.5" title="Giliran Anda bertindak di tab ini">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#FF8040] opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[#FF8040]"></span>
                        </span>
                    @elseif ($indicator === 'waiting')
                        <span class="inline-flex h-2 w-2 rounded-full bg-[#0046FF]"
                            title="Sedang menunggu pihak terkait"></span>
                    @endif

                    @if ($tab['key'] === 'riwayat' && $request->histories->isNotEmpty())
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">
                            {{ $request->histories->count() }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>

        {{-- Tab 1: Informasi & Barang --}}
        @if ($activeTab === 'informasi')
            <div class="space-y-4">
                @if ($notice = $tabNotices['informasi'] ?? null)
                    @php
                        $nStyle = match ($notice['type']) {
                            'action' => [
                                'wrap' => 'border-blue-200 bg-blue-50/70 text-slate-800',
                                'iconBg' => 'bg-[#0046FF] text-white',
                                'icon' => 'o-arrow-right-circle',
                            ],
                            'done' => [
                                'wrap' => 'border-emerald-200 bg-emerald-50/70 text-emerald-950',
                                'iconBg' => 'bg-emerald-600 text-white',
                                'icon' => 'o-check-circle',
                            ],
                            default => [
                                'wrap' => 'border-slate-200 bg-slate-50 text-slate-700',
                                'iconBg' => 'bg-white text-[#0046FF] border border-slate-200',
                                'icon' => 'o-clock',
                            ],
                        };
                    @endphp
                    <div class="flex items-start gap-3 rounded-xl border p-4 text-sm {{ $nStyle['wrap'] }}">
                        <span
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $nStyle['iconBg'] }}">
                            <x-mary-icon :name="$nStyle['icon']" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="font-bold">{{ $notice['title'] }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed opacity-90">{{ $notice['desc'] }}</p>
                        </div>
                    </div>
                @endif

                <section class="rounded-xl border border-gray-100 bg-white p-5 text-sm text-gray-700 shadow-sm">
                    <div class="mb-5 border-b border-gray-100 pb-4">
                        <h2 class="font-bold text-gray-900">Informasi Pengajuan</h2>
                        <p class="mt-1 text-xs text-gray-500">Identitas pengajuan dan sekolah pemohon.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-x-8 gap-y-3 md:grid-cols-2">
                        <div class="space-y-3">
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Nomor Pengajuan</span>
                                <span class="font-mono font-medium text-gray-800">{{ $requestNumber }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Sekolah</span>
                                <span class="font-medium text-gray-800">{{ $school?->name ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">NPSN</span>
                                <span class="text-gray-800">{{ $school?->npsn ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Sumber Dana</span>
                                <span class="text-gray-800">{{ $request->fundingSource?->name ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Tahun Anggaran</span>
                                <span class="text-gray-800">{{ $request->budgetYear?->name ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Kategori</span>
                                <span class="text-gray-800">{{ $request->packageCategory?->name ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Tanggal Pengajuan</span>
                                <span
                                    class="text-gray-800">{{ $request->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm') }}</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Nama Pengaju</span>
                                <span
                                    class="font-medium text-gray-800">{{ $schoolAccount?->name ?? ($school?->principal_name ?? '-') }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">NIP Kepala Sekolah</span>
                                <span class="text-gray-800">{{ $school?->principal_nip ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Email</span>
                                <span
                                    class="break-all text-gray-800">{{ $schoolAccount?->email ?? ($school?->email ?? '-') }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">No. Telepon</span>
                                <span class="text-gray-800">{{ $school?->phone_number ?? '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <span class="text-gray-500">Alamat Sekolah</span>
                                <span class="text-gray-800">{{ $school?->address ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <div class="mb-3">
                            <h3 class="font-bold text-gray-900">Detail Barang</h3>
                            <p class="text-xs text-gray-500">Rincian barang dan estimasi yang diajukan sekolah.</p>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-gray-100">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                                    <tr>
                                        <th class="whitespace-nowrap px-3 py-3">No.</th>
                                        <th class="whitespace-nowrap px-3 py-3">Nama Barang</th>
                                        <th class="px-3 py-3">Spesifikasi</th>
                                        <th class="whitespace-nowrap px-3 py-3">Satuan</th>
                                        <th class="whitespace-nowrap px-3 py-3 text-right">Jumlah</th>
                                        <th class="whitespace-nowrap px-3 py-3 text-right">Estimasi Harga</th>
                                        <th class="whitespace-nowrap px-3 py-3 text-right">Total Estimasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($request->items as $index => $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-3 text-gray-400">{{ $index + 1 }}</td>
                                            <td class="px-3 py-3 font-medium text-gray-900">{{ $item->item_name }}
                                            </td>
                                            <td class="px-3 py-3 text-gray-600">{{ $item->specification ?: '-' }}</td>
                                            <td class="px-3 py-3 text-gray-600">{{ $item->unit?->name ?? '-' }}</td>
                                            <td class="px-3 py-3 text-right text-gray-700">
                                                {{ number_format($item->quantity, 0, ',', '.') }}</td>
                                            <td class="whitespace-nowrap px-3 py-3 text-right text-gray-700">
                                                Rp {{ number_format((float) $item->estimated_price, 0, ',', '.') }}
                                            </td>
                                            <td
                                                class="whitespace-nowrap px-3 py-3 text-right font-semibold text-gray-900">
                                                Rp {{ number_format($item->estimatedAmount(), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-8 text-center text-gray-500">Belum ada
                                                barang pada pengajuan ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-blue-50/60">
                                    <tr>
                                        <td colspan="6" class="px-3 py-3 text-right font-semibold text-gray-700">
                                            Total Estimasi</td>
                                        <td
                                            class="whitespace-nowrap px-3 py-3 text-right font-extrabold text-[#0046FF]">
                                            Rp {{ number_format($estimatedTotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Nilai ini merupakan estimasi dari sekolah, bukan
                            penawaran atau nilai final.</p>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab 2: Ruang Negosiasi --}}
        @if ($activeTab === 'negosiasi')
            <div class="space-y-4">
                @if ($notice = $tabNotices['negosiasi'] ?? null)
                    @php
                        $nStyle = match ($notice['type']) {
                            'action' => [
                                'wrap' => 'border-blue-200 bg-blue-50/70 text-slate-800',
                                'iconBg' => 'bg-[#0046FF] text-white',
                                'icon' => 'o-arrow-right-circle',
                            ],
                            'done' => [
                                'wrap' => 'border-emerald-200 bg-emerald-50/70 text-emerald-950',
                                'iconBg' => 'bg-emerald-600 text-white',
                                'icon' => 'o-check-circle',
                            ],
                            default => [
                                'wrap' => 'border-slate-200 bg-slate-50 text-slate-700',
                                'iconBg' => 'bg-white text-[#0046FF] border border-slate-200',
                                'icon' => 'o-clock',
                            ],
                        };
                    @endphp
                    <div class="flex items-start gap-3 rounded-xl border p-4 text-sm {{ $nStyle['wrap'] }}">
                        <span
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $nStyle['iconBg'] }}">
                            <x-mary-icon :name="$nStyle['icon']" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="font-bold">{{ $notice['title'] }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed opacity-90">{{ $notice['desc'] }}</p>
                        </div>
                    </div>
                @endif

                @if ($request->canShowNegotiation())
                    <livewire:procurement.negotiation-panel :procurement-request="$request"
                        wire:key="negotiation-panel-{{ $request->id }}" />
                @endif
            </div>
        @endif

        {{-- Tab 3: Pengiriman & Penerimaan --}}
        @if ($activeTab === 'pengiriman')
            <div class="space-y-4">
                @if ($notice = $tabNotices['pengiriman'] ?? null)
                    @php
                        $nStyle = match ($notice['type']) {
                            'action' => [
                                'wrap' => 'border-blue-200 bg-blue-50/70 text-slate-800',
                                'iconBg' => 'bg-[#0046FF] text-white',
                                'icon' => 'o-arrow-right-circle',
                            ],
                            'done' => [
                                'wrap' => 'border-emerald-200 bg-emerald-50/70 text-emerald-950',
                                'iconBg' => 'bg-emerald-600 text-white',
                                'icon' => 'o-check-circle',
                            ],
                            default => [
                                'wrap' => 'border-slate-200 bg-slate-50 text-slate-700',
                                'iconBg' => 'bg-white text-[#0046FF] border border-slate-200',
                                'icon' => 'o-clock',
                            ],
                        };
                    @endphp
                    <div class="flex items-start gap-3 rounded-xl border p-4 text-sm {{ $nStyle['wrap'] }}">
                        <span
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $nStyle['iconBg'] }}">
                            <x-mary-icon :name="$nStyle['icon']" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="font-bold">{{ $notice['title'] }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed opacity-90">{{ $notice['desc'] }}</p>
                        </div>
                    </div>
                @endif

                @if ($isInternal && $request->canShowProcessing())
                    <livewire:procurement.procurement-processing-panel :procurement-request="$request"
                        wire:key="processing-panel-{{ $request->id }}" />
                @endif

                @if ($request->canShowReceiptVerification())
                    <livewire:procurement.verification-panel :procurement-request="$request"
                        wire:key="verification-panel-{{ $request->id }}" />
                @endif
            </div>
        @endif

        {{-- Tab 4: Dokumen --}}
        @if ($activeTab === 'dokumen')
            <div class="space-y-4">
                @if ($notice = $tabNotices['dokumen'] ?? null)
                    @php
                        $nStyle = match ($notice['type']) {
                            'action' => [
                                'wrap' => 'border-blue-200 bg-blue-50/70 text-slate-800',
                                'iconBg' => 'bg-[#0046FF] text-white',
                                'icon' => 'o-arrow-right-circle',
                            ],
                            'done' => [
                                'wrap' => 'border-emerald-200 bg-emerald-50/70 text-emerald-950',
                                'iconBg' => 'bg-emerald-600 text-white',
                                'icon' => 'o-check-circle',
                            ],
                            default => [
                                'wrap' => 'border-slate-200 bg-slate-50 text-slate-700',
                                'iconBg' => 'bg-white text-[#0046FF] border border-slate-200',
                                'icon' => 'o-clock',
                            ],
                        };
                    @endphp
                    <div class="flex items-start gap-3 rounded-xl border p-4 text-sm {{ $nStyle['wrap'] }}">
                        <span
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $nStyle['iconBg'] }}">
                            <x-mary-icon :name="$nStyle['icon']" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="font-bold">{{ $notice['title'] }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed opacity-90">{{ $notice['desc'] }}</p>
                        </div>
                    </div>
                @endif

                <livewire:procurement.document-panel :procurement-request="$request"
                    wire:key="request-document-panel-{{ $request->id }}" />
            </div>
        @endif

        {{-- Tab 5: Riwayat Aktivitas --}}
        @if ($activeTab === 'riwayat')
            <section class="rounded-xl border border-gray-100 bg-white p-5 text-sm text-gray-700 shadow-sm">
                <div class="mb-5 border-b border-gray-100 pb-4">
                    <h2 class="font-bold text-gray-900">Riwayat Status &amp; Aktivitas</h2>
                    <p class="mt-1 text-xs text-gray-500">Jejak perubahan status dan log aktivitas pengajuan secara
                        kronologis.</p>
                </div>

                @if ($request->histories->isEmpty())
                    <div class="flex gap-3">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#0046FF]">
                            <x-mary-icon name="o-document-plus" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="font-semibold text-gray-900">Pengajuan dibuat</p>
                            <p class="text-xs text-gray-500">
                                {{ $request->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm') }}</p>
                        </div>
                    </div>
                @else
                    <ol class="relative isolate ml-2">
                        @foreach ($request->histories->sortByDesc('created_at') as $history)
                            @php
                                $formattedNotes = $history->notes;
                                if (
                                    $formattedNotes &&
                                    \Illuminate\Support\Str::startsWith($formattedNotes, 'Perubahan revisi: {')
                                ) {
                                    $decoded = json_decode(
                                        \Illuminate\Support\Str::after($formattedNotes, 'Perubahan revisi: '),
                                        true,
                                    );
                                    if (is_array($decoded)) {
                                        $updatedCount = count($decoded['items']['updated'] ?? []);
                                        $createdCount = count($decoded['items']['created'] ?? []);
                                        $deletedCount = count($decoded['items']['deleted'] ?? []);
                                        $formattedNotes = "Sekolah memperbarui data pengajuan ({$updatedCount} item diubah, {$createdCount} ditambah, {$deletedCount} dihapus).";
                                    }
                                }
                            @endphp
                            <li class="relative flex gap-4 pb-6 last:pb-0">
                                @if (!$loop->last)
                                    <span class="absolute bottom-0 left-3.5 top-7 border-l border-gray-200"></span>
                                @endif
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-white text-[#0046FF]">
                                    <x-mary-icon :name="$history->status === \App\Models\ProcurementRequest::STATUS_REJECTED
                                        ? 'o-x-circle'
                                        : ($history->status === \App\Models\ProcurementRequest::STATUS_COMPLETED
                                            ? 'o-check-circle'
                                            : 'o-arrow-path')" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1 rounded-lg border border-gray-100 bg-gray-50/70 p-3.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-semibold text-gray-900">
                                            {{ \App\Models\ProcurementRequest::STATUS_LABELS[$history->status] ?? \Illuminate\Support\Str::headline($history->status) }}
                                        </p>
                                        <span class="text-xs text-gray-500">
                                            {{ $history->created_at?->locale('id')->isoFormat('D MMM YYYY HH:mm') ?? '-' }}
                                            @if ($history->createdBy)
                                                · <strong
                                                    class="font-medium text-gray-700">{{ $history->createdBy->name }}</strong>
                                            @endif
                                        </span>
                                    </div>
                                    @if ($formattedNotes)
                                        <p class="mt-1.5 whitespace-pre-line text-xs leading-relaxed text-gray-600">
                                            {{ $formattedNotes }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endif
    </main>

    <x-mary-modal wire:model="confirmingDeleteDraft" title="Hapus Pengajuan" class="backdrop-blur"
        title-class="text-[#FF8040]">
        <div class="flex items-start gap-4">
            <div class="shrink-0 rounded-full bg-[#FF8040]/10 p-3 text-[#FF8040]">
                <x-mary-icon name="o-trash" class="h-7 w-7" />
            </div>
            <div>
                <p class="font-semibold text-black">
                    Hapus <span class="text-[#FF8040]">"{{ $requestNumber }}"</span>?
                </p>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    Draf pengajuan dan rincian barangnya akan dihapus dari tampilan, tetapi tetap disimpan sebagai
                    riwayat dan tidak dapat dipulihkan langsung.
                </p>
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Batal" wire:click="$set('confirmingDeleteDraft', false)"
                class="btn-ghost text-black" />
            <x-mary-button label="Ya, Hapus" wire:click="deleteDraft" wire:loading.attr="disabled"
                spinner="deleteDraft" class="border-none bg-[#FF8040] text-white" />
        </x-slot:actions>
    </x-mary-modal>
</div>
