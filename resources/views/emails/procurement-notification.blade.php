{{-- PRD v1.3: Gunakan istilah "Anak Perusahaan", jangan pernah tampilkan "Supplier". --}}
@php
    $pr = $procurementRequest;
    $event = $notification?->event_type;
    $referenceNumber = $pr ? '#' . $pr->id : '-';
    $schoolName = $pr?->school?->name ?? '-';
    $subsidiaryName = $pr?->subsidiary?->name;
    $statusLabel = $pr?->status_label ?? '-';

    $actionLabels = [
        'submitted' => 'Periksa & Verifikasi Pengajuan',
        'verified' => 'Lanjutkan Proses Penawaran',
        'supplier_assigned' => 'Lanjutkan Proses Penawaran',
        'needs_revision' => 'Perbaiki Data Pengajuan',
        'rejected' => 'Lihat Alasan Penolakan',
        'negotiation_offer' => 'Tinjau Riwayat Negosiasi',
        'negotiation_accepted' => 'Tinjau Riwayat Negosiasi',
        'negotiation_rejected' => 'Tinjau Riwayat Negosiasi',
        'price_agreed' => 'Lihat Kesepakatan Harga',
        'processing_started' => 'Pantau Pengiriman Barang',
        'delivery_recorded' => 'Pantau Pengiriman Barang',
        'receipt_recorded' => 'Verifikasi Penerimaan Barang',
        'receipt_verified' => 'Lihat Status Penerimaan',
        'receipt_issue_resolved' => 'Lihat Status Penerimaan',
        'document_generated' => 'Unduh Dokumen Resmi',
        'record_payment' => 'Lihat Detail Pembayaran & Transaksi',
        'confirm_payment' => 'Lihat Detail Pembayaran & Transaksi',
        'completed' => 'Lihat Detail Pembayaran & Transaksi',
    ];
    $actionLabel = $actionLabels[$event] ?? 'Lihat Detail Pengadaan';
    $actionUrl = $pr ? route('procurement.show', $pr) : url('/');
@endphp

<x-mail::message>
# Notifikasi Pengadaan

{{ $messageBody }}

<x-mail::panel>
<strong>Nomor Referensi:</strong> {{ $referenceNumber }}<br>
<strong>Sekolah:</strong> {{ $schoolName }}<br>
@if ($subsidiaryName)
<strong>Anak Perusahaan:</strong> {{ $subsidiaryName }}<br>
@endif
<strong>Status Transaksi:</strong> {{ $statusLabel }}
</x-mail::panel>

<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>

@if (!empty($documents))
## Dokumen Resmi Tersedia

@foreach ($documents as $doc)
- {{ \App\Models\ProcurementDocument::documentTypeLabel($doc->document_type) }} — {{ $doc->document_number ?? 'Tanpa Nomor' }}
@endforeach
@endif

Email ini dikirim secara otomatis oleh Sistem Informasi Pengadaan Barang Sekolah. Mohon untuk tidak membalas email ini.

Thanks,<br>
Sistem Scholaproc
</x-mail::message>