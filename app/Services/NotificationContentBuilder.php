<?php

namespace App\Services;

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\User;

class NotificationContentBuilder
{
    public static function buildSubmitted(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';
        $itemCount = $request->items()->count();

        return "Pengajuan pengadaan baru telah diajukan.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Kategori: {$categoryName}\n"
            ."Total item: {$itemCount}\n\n"
            .'Silakan verifikasi pengajuan ini.';
    }

    public static function buildVerified(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Pengajuan pengadaan telah diverifikasi oleh Admin.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Kategori: {$categoryName}\n\n"
            .'Admin Pusat akan menetapkan Anak Perusahaan untuk proses penawaran.';
    }

    public static function buildRejected(ProcurementRequest $request, string $reason): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Pengajuan pengadaan DITOLAK.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Kategori: {$categoryName}\n"
            ."Alasan: {$reason}\n\n"
            .'Silakan perbaiki pengajuan dan submit ulang.';
    }

    public static function buildNeedsRevision(ProcurementRequest $request, string $reason): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Pengajuan pengadaan perlu REVISI.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Kategori: {$categoryName}\n"
            ."Alasan: {$reason}\n\n"
            .'Silakan perbaiki dan ajukan kembali.';
    }

    public static function buildSupplierAssigned(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $subsidiaryName = $request->subsidiary?->name ?? 'Anak Perusahaan';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Anak Perusahaan telah ditunjuk untuk pengadaan.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Anak Perusahaan: {$subsidiaryName}\n"
            ."Kategori: {$categoryName}\n\n"
            .'Negosiasi harga dapat dimulai.';
    }

    public static function buildNegotiationOffer(
        ProcurementRequest $request,
        ProcurementRequestItem $item,
        ProcurementNegotiation $negotiation
    ): string {
        $itemName = $item->item_name;
        $price = number_format($item->official_price ?? $negotiation->offered_price, 0, ',', '.');
        $round = $negotiation->round_number;
        $notes = $negotiation->notes ?: '-';

        return "Tawaran harga baru untuk item pengadaan.\n\n"
            ."Item: {$itemName}\n"
            ."Harga yang ditawarkan: Rp {$price}\n"
            ."Putaran: {$round}\n"
            ."Catatan: {$notes}\n\n"
            .'Silakan tinjau tawaran ini.';
    }

    public static function buildNegotiationDecided(
        ProcurementRequest $request,
        ProcurementRequestItem $item,
        ProcurementNegotiation $negotiation,
        string $action
    ): string {
        $itemName = $item->item_name;
        $price = number_format($negotiation->offered_price, 0, ',', '.');
        $statusText = $action === 'accepted' ? 'DITERIMA' : 'DITOLAK';

        $message = "Keputusan negosiasi harga.\n\n"
            ."Item: {$itemName}\n"
            ."Harga: Rp {$price}\n"
            ."Status: {$statusText}\n";

        if ($action === 'accepted') {
            $message .= "\nHarga resmi untuk item ini telah ditetapkan.";
        } else {
            $reason = $negotiation->notes ?: 'Tidak ada alasan spesifik';
            $message .= "Alasan: {$reason}\n";
        }

        return $message;
    }

    public static function buildItemsPrepared(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $subsidiaryName = $request->subsidiary?->name ?? 'Anak Perusahaan';
        $itemCount = $request->items()->count();

        return "Barang pengadaan telah disiapkan oleh Anak Perusahaan.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Anak Perusahaan: {$subsidiaryName}\n"
            ."Total item: {$itemCount}\n\n"
            .'Barang siap untuk dikirim.';
    }

    public static function buildReceiptVerified(ProcurementRequest $request, array $context = []): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $status = $context['status'] ?? $request->status;
        $hasIssues = $context['has_issues'] ?? false;
        $isFullyReceived = $context['is_fully_received'] ?? false;

        $statusLabels = [
            ProcurementRequest::STATUS_RECEIVED => 'DITERIMA LENGKAP',
            ProcurementRequest::STATUS_PARTIALLY_RECEIVED => 'DITERIMA SEBAGIAN',
            ProcurementRequest::STATUS_DISPUTED => 'DISPUTED (ADA KENDALA)',
        ];

        $statusText = $statusLabels[$status] ?? $status;

        return "Penerimaan barang telah diverifikasi.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Hasil Verifikasi: {$statusText}\n\n"
            .($hasIssues ? 'Ada kendala yang perlu diselesaikan.' : 'Semua barang diterima dengan baik.')
            ."\nSilakan lanjutkan proses administrasi.";
    }

    public static function buildCompleted(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $subsidiaryName = $request->subsidiary?->name ?? 'Anak Perusahaan';
        $grandTotal = $request->grand_total ? number_format($request->grand_total, 0, ',', '.') : '-';

        return "Pengadaan telah SELESAI.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Anak Perusahaan: {$subsidiaryName}\n"
            ."Total transaksi: Rp {$grandTotal}\n\n"
            .'Dokumen pengadaan sudah tersedia untuk diunduh.';
    }

    public static function buildDocumentGenerated(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';
        $documentCount = $request->generatedDocuments()->count();

        return "Dokumen pengadaan telah tersedia untuk diunduh.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Kategori: {$categoryName}\n"
            ."Total dokumen: {$documentCount}\n\n"
            .'Silakan unduh melalui sistem pengadaan.';
    }

    public static function buildPaymentRecorded(
        ProcurementRequest $request,
        float $amount,
        string $method,
        string $reference,
        string $paidAt
    ): string {
        $schoolName = $request->school->name ?? 'Sekolah';
        $formattedAmount = number_format($amount, 0, ',', '.');

        return "Pembayaran pengadaan telah dicatat.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Jumlah: Rp {$formattedAmount}\n"
            ."Metode: {$method}\n"
            ."Referensi: {$reference}\n"
            ."Tanggal: {$paidAt}\n\n"
            .'Pembayaran menunggu konfirmasi dari Admin Pusat.';
    }

    public static function buildPaymentConfirmed(
        ProcurementRequest $request,
        float $amount,
        ?string $receiptNumber = null
    ): string {
        $schoolName = $request->school->name ?? 'Sekolah';
        $formattedAmount = number_format($amount, 0, ',', '.');
        $receiptLine = $receiptNumber ? "\nNomor Kuitansi: {$receiptNumber}" : '';

        return "Pembayaran pengadaan telah dikonfirmasi.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Jumlah: Rp {$formattedAmount}{$receiptLine}\n\n"
            .'Kuitansi pembayaran telah tersedia untuk diunduh.';
    }

    public static function buildProcessingStarted(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';
        $subsidiaryName = $request->subsidiary->name ?? 'Anak Perusahaan';
        $poNumber = $request->documents()
            ->where('document_type', 'purchase_order')
            ->whereNotNull('issued_at')
            ->latest('version')
            ->first()?->document_number ?? '-';

        return "Pemrosesan pengadaan telah dimulai.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Kategori: {$categoryName}\n"
            ."Anak Perusahaan: {$subsidiaryName}\n"
            ."Surat Pesanan: {$poNumber}\n\n"
            .'Anak Perusahaan akan menyiapkan barang sesuai pesanan.';
    }

    public static function buildDeliveryRecorded(ProcurementRequest $request, array $context): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $deliveredAt = $context['delivered_at'] ?? '-';
        $deliveryId = $context['delivery_id'] ?? '-';

        return "Pengiriman barang telah dicatat.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Tanggal Kirim: {$deliveredAt}\n"
            ."Referensi: {$deliveryId}\n\n"
            .'Silakan siapkan penerimaan barang.';
    }

    public static function buildReceiptRecorded(ProcurementRequest $request, array $context): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $receivedAt = $context['received_at'] ?? '-';
        $hasIssues = $context['has_issues'] ?? false;
        $isPartial = $context['is_partial'] ?? false;

        $statusText = $hasIssues ? 'ADA KENDALA' : ($isPartial ? 'SEBAGIAN' : 'LENGKAP');

        return "Penerimaan barang dicatat oleh sekolah.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Tanggal Terima: {$receivedAt}\n"
            ."Status: {$statusText}\n\n"
            .'Mohon verifikasi penerimaan ini.';
    }

    public static function buildReceiptIssueResolved(ProcurementRequest $request, array $context): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $resolution = $context['resolution'] ?? '-';

        return "Kendala penerimaan telah diselesaikan.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Penyelesaian: {$resolution}\n\n"
            .'Proses pengadaan dapat dilanjutkan.';
    }

    public static function buildPriceAgreed(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $subsidiaryName = $request->subsidiary->name ?? 'Anak Perusahaan';
        $grandTotal = $request->grand_total ? number_format($request->grand_total, 0, ',', '.') : '-';

        return "Harga pengadaan telah disepakati kedua pihak.\n\n"
            ."Sekolah: {$schoolName}\n"
            ."Anak Perusahaan: {$subsidiaryName}\n"
            ."Total Transaksi: Rp {$grandTotal}\n\n"
            .'Dokumen negosiasi tersedia. Proses ke tahap pemrosesan.';
    }

    public static function buildCancelled(ProcurementRequest $request, string $reason, ?User $actor = null): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $reference = '#'.$request->id;
        $cancelledBy = $actor?->name ?? 'Pengguna';

        return "Pengajuan pengadaan DIBATALKAN.\n\n"
            ."Nomor Referensi: {$reference}\n"
            ."Sekolah: {$schoolName}\n"
            ."Dibatalkan oleh: {$cancelledBy}\n"
            ."Alasan: {$reason}\n\n"
            .'Pengajuan ini tidak dapat dilanjutkan.';
    }
}
