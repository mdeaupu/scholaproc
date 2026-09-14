<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ProcurementRequest;
use App\Models\User;

class NotificationService
{
    private string $adminCvPhone;
    private string $adminCvEmail;

    public function __construct()
    {
        $this->adminCvPhone = config('waha.admin_cv_phone', '');
        $this->adminCvEmail = config('waha.admin_cv_email', '');
    }

    /**
     * Kirim notifikasi WhatsApp dan Email terkait suatu procurement event.
     */
    public function notify(ProcurementRequest $request, string $event, array $context = [], ?User $triggeredBy = null): void
    {
        $message = $this->buildMessage($request, $event, $context);

        if (!$message) {
            return;
        }

        $recipientPhone = $this->resolveRecipientPhone($request, $event);
        $recipientEmail = $this->resolveRecipientEmail($request, $event);

        if ($recipientPhone) {
            $this->createNotification($request, $event, Notification::CHANNEL_WHATSAPP, $recipientPhone, $message, $triggeredBy);
        }

        if ($recipientEmail) {
            $this->createNotification($request, $event, Notification::CHANNEL_EMAIL, $recipientEmail, $message, $triggeredBy);
        }
    }

    /**
     * Cek apakah notifikasi duplikat sudah ada dalam X menit terakhir.
     */
    private function isDuplicate(
        ProcurementRequest $request,
        string $event,
        string $channel,
        int $withinMinutes = 5
    ): bool {
        return Notification::where('procurement_request_id', $request->id)
            ->where('event_type', $event)
            ->where('channel', $channel)
            ->where('created_at', '>=', now()->subMinutes($withinMinutes))
            ->exists();
    }

    /**
     * Buat record notifikasi dan dispatch queue job.
     */
    public function createNotification(
        ProcurementRequest $request,
        string $event,
        string $channel,
        string $recipient,
        string $message,
        ?User $triggeredBy = null
    ): ?Notification {
        if ($this->isDuplicate($request, $event, $channel)) {
            return null;
        }

        $notification = Notification::create([
            'procurement_request_id' => $request->id,
            'event_type' => $event,
            'triggered_by' => $triggeredBy?->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'message_content' => $message,
            'status' => Notification::STATUS_PENDING,
        ]);

        // Observer akan dispatch queue job otomatis

        return $notification;
    }

    /**
     * Build message berdasarkan event type.
     */
    private function buildMessage(ProcurementRequest $request, string $event, array $context): ?string
    {
        return match ($event) {
            'submitted' => NotificationContentBuilder::buildSubmitted($request),
            'verified' => NotificationContentBuilder::buildVerified($request),
            'rejected' => NotificationContentBuilder::buildRejected($request, $context['reason'] ?? ''),
            'supplier_assigned' => NotificationContentBuilder::buildSupplierAssigned($request),
            'negotiation_offer' => NotificationContentBuilder::buildNegotiationOffer(
                $request,
                $context['item'],
                $context['negotiation']
            ),
            'negotiation_accepted' => NotificationContentBuilder::buildNegotiationDecided(
                $request,
                $context['item'],
                $context['negotiation'],
                'accepted'
            ),
            'negotiation_rejected' => NotificationContentBuilder::buildNegotiationDecided(
                $request,
                $context['item'],
                $context['negotiation'],
                'rejected'
            ),
            'items_prepared' => NotificationContentBuilder::buildItemsPrepared($request),
            'receipt_verified' => NotificationContentBuilder::buildReceiptVerified($request),
            'completed' => NotificationContentBuilder::buildCompleted($request),
            'document_generated' => NotificationContentBuilder::buildDocumentGenerated($request),
            default => null,
        };
    }

    /**
     * Tentukan recipient phone berdasarkan event.
     * - Admin CV menerima: submitted, receipt_verified
     * - School menerima: verified, rejected, supplier_assigned, negotiation_offer, negotiation_decided, items_prepared, completed
     */
    private function resolveRecipientPhone(ProcurementRequest $request, string $event): ?string
    {
        return match ($event) {
            'submitted', 'receipt_verified' => $this->adminCvPhone ?: null,
            default => $request->school->phone_number ?? null,
        };
    }

    /**
     * Tentukan recipient email berdasarkan event.
     */
    private function resolveRecipientEmail(ProcurementRequest $request, string $event): ?string
    {
        return match ($event) {
            'submitted', 'receipt_verified' => $this->adminCvEmail ?: null,
            default => $request->school->email ?? null,
        };
    }
}
