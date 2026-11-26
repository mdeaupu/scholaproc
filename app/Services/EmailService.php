<?php

namespace App\Services;

use App\Mail\ProcurementNotification;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function send(Notification $notification): array
    {
        try {
            $procurementRequest = $notification->procurementRequest;
            $schoolName = $procurementRequest?->school?->name ?? 'Sistem';
            $referenceNumber = $procurementRequest ? '#'.$procurementRequest->id : '-';
            $eventLabel = $this->eventLabel($notification->event_type);

            $subject = "[Scholaproc] {$eventLabel} — {$referenceNumber} ({$schoolName})";

            $documents = $procurementRequest
                ? $procurementRequest->documents()->whereNotNull('issued_at')->get()->toArray()
                : [];

            Mail::to($notification->recipient)->send(new ProcurementNotification(
                notification: $notification,
                procurementRequest: $procurementRequest,
                subjectLine: $subject,
                messageBody: (string) $notification->message_content,
                documents: $documents,
            ));

            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Email send failed', [
                'notification_id' => $notification->id,
                'to' => $notification->recipient,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function isAvailable(): bool
    {
        $mailer = config('mail.default');

        // Mailer log/array dianggap tersedia untuk pengujian lokal (tidak crash).
        return in_array($mailer, ['log', 'array'], true) || $mailer !== 'log';
    }

    private function eventLabel(?string $event): string
    {
        return match ($event) {
            'submitted' => 'Pengajuan Baru',
            'verified' => 'Pengajuan Terverifikasi',
            'rejected' => 'Pengajuan Ditolak',
            'needs_revision' => 'Perlu Revisi',
            'supplier_assigned' => 'Penugasan Anak Perusahaan',
            'negotiation_offer' => 'Tawaran Harga',
            'negotiation_accepted' => 'Tawaran Diterima',
            'negotiation_rejected' => 'Tawaran Ditolak',
            'items_prepared' => 'Barang Disiapkan',
            'processing_started' => 'Pemrosesan Dimulai',
            'delivery_recorded' => 'Pengiriman Tercatat',
            'receipt_recorded' => 'Penerimaan Tercatat',
            'receipt_verified' => 'Penerimaan Terverifikasi',
            'receipt_issue_resolved' => 'Kendala Diselesaikan',
            'price_agreed' => 'Harga Disepakati',
            'record_payment' => 'Pembayaran Tercatat',
            'confirm_payment' => 'Pembayaran Dikonfirmasi',
            'document_generated' => 'Dokumen Tersedia',
            'completed' => 'Pengadaan Selesai',
            default => 'Notifikasi Pengadaan',
        };
    }
}
