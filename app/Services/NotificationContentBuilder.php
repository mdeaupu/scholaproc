<?php

namespace App\Services;

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;

class NotificationContentBuilder
{
    public static function buildSubmitted(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';
        $itemCount = $request->items()->count();

        return "Pengajuan pengadaan baru telah diajukan.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Kategori: {$categoryName}\n"
            . "Total item: {$itemCount}\n\n"
            . "Silakan verifikasi pengajuan ini.";
    }

    public static function buildVerified(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Pengajuan pengadaan telah diverifikasi oleh Admin.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Kategori: {$categoryName}\n\n"
            . "Silakan tentukan supplier untuk pengadaan ini.";
    }

    public static function buildRejected(ProcurementRequest $request, string $reason): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Pengajuan pengadaan DITOLAK.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Kategori: {$categoryName}\n"
            . "Alasan: {$reason}\n\n"
            . "Silakan perbaiki pengajuan dan submit ulang.";
    }

    public static function buildSupplierAssigned(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $supplierName = $request->supplier->company_name ?? '-';
        $categoryName = $request->packageCategory->name ?? '-';

        return "Supplier telah ditunjuk untuk pengadaan.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Supplier: {$supplierName}\n"
            . "Kategori: {$categoryName}\n\n"
            . "Negosiasi harga dapat dimulai.";
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
            . "Item: {$itemName}\n"
            . "Harga yang ditawarkan: Rp {$price}\n"
            . "Putaran: {$round}\n"
            . "Catatan: {$notes}\n\n"
            . "Silakan tinjau tawaran ini.";
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
            . "Item: {$itemName}\n"
            . "Harga: Rp {$price}\n"
            . "Status: {$statusText}\n";

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
        $supplierName = $request->supplier->company_name ?? '-';
        $itemCount = $request->items()->count();

        return "Barang pengadaan telah disiapkan oleh supplier.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Supplier: {$supplierName}\n"
            . "Total item: {$itemCount}\n\n"
            . "Barang siap untuk dikirim.";
    }

    public static function buildReceiptVerified(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $verifierName = $request->verifiedBy?->name ?? 'Admin Sekolah';
        $verifiedAt = $request->verified_at ? $request->verified_at->format('d M Y H:i') : now()->format('d M Y H:i');

        return "Barang pengadaan telah diverifikasi diterima.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Diverifikasi oleh: {$verifierName}\n"
            . "Tanggal: {$verifiedAt} WIB";
    }

    public static function buildCompleted(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $supplierName = $request->supplier->company_name ?? '-';
        $grandTotal = $request->grand_total ? number_format($request->grand_total, 0, ',', '.') : '-';

        return "Pengadaan telah SELESAI.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Supplier: {$supplierName}\n"
            . "Total transaksi: Rp {$grandTotal}\n\n"
            . "Dokumen pengadaan sudah tersedia untuk diunduh.";
    }

    public static function buildDocumentGenerated(ProcurementRequest $request): string
    {
        $schoolName = $request->school->name ?? 'Sekolah';
        $categoryName = $request->packageCategory->name ?? '-';
        $documentCount = $request->generatedDocuments()->count();

        return "Dokumen pengadaan telah tersedia untuk diunduh.\n\n"
            . "Sekolah: {$schoolName}\n"
            . "Kategori: {$categoryName}\n"
            . "Total dokumen: {$documentCount}\n\n"
            . "Silakan unduh melalui sistem pengadaan.";
    }
}
