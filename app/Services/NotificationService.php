<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ProcurementNegotiation;
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
        $message = $this->buildMessage($request, $event, $context, $triggeredBy);

        if (! $message) {
            return;
        }

        $recipientPhone = $this->resolveRecipientPhone($request, $event, $context, $triggeredBy);
        $recipientEmail = $this->resolveRecipientEmail($request, $event, $context, $triggeredBy);

        if ($recipientPhone) {
            $this->createNotification($request, $event, Notification::CHANNEL_WHATSAPP, $recipientPhone, $message, $triggeredBy, $context);
        }

        if ($recipientEmail) {
            $this->createNotification($request, $event, Notification::CHANNEL_EMAIL, $recipientEmail, $message, $triggeredBy, $context);
        } elseif ($this->isSchoolEvent($event)) {
            // PRD M12: event sekolah tanpa email valid tetap dicatat sebagai gagal
            // agar riwayat in-app tersedia tanpa memicu pengiriman email.
            $this->createFailedEmailNotification($request, $event, $message, $triggeredBy, $context);
        }
    }

    /**
     * Event yang menargetkan sekolah (bukan admin pusat).
     */
    private function isSchoolEvent(string $event): bool
    {
        return ! in_array($event, ['submitted', 'receipt_recorded'], true);
    }

    /**
     * Catat notifikasi email gagal tanpa dispatch (PRD M12 isolation).
     */
    private function createFailedEmailNotification(
        ProcurementRequest $request,
        string $event,
        string $message,
        ?User $triggeredBy = null,
        array $context = []
    ): ?Notification {
        if ($this->isDuplicate($request, $event, Notification::CHANNEL_EMAIL, $context)) {
            return null;
        }

        return Notification::create([
            'procurement_request_id' => $request->id,
            'event_type' => $event,
            'triggered_by' => $triggeredBy?->id,
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => '-',
            'message_content' => $message,
            'status' => Notification::STATUS_FAILED,
            'error_message' => 'Email penerima tidak tersedia atau tidak valid',
            'meta' => array_filter([
                'item_id' => $context['item']?->id ?? null,
                'negotiation_id' => $context['negotiation']?->id ?? null,
                'receipt_id' => $context['receipt_id'] ?? null,
                'delivery_id' => $context['delivery_id'] ?? null,
                'document_type' => $context['document_type'] ?? null,
                'document_id' => $context['document_id'] ?? null,
                'offered_by' => $context['offered_by'] ?? null,
                'decided_by' => $context['decided_by'] ?? null,
            ]),
        ]);
    }

    /**
     * Cek apakah notifikasi duplikat sudah ada dalam X menit terakhir.
     */
    private function isDuplicate(
        ProcurementRequest $request,
        string $event,
        string $channel,
        array $context = [],
        int $withinMinutes = 5
    ): bool {
        $query = Notification::where('procurement_request_id', $request->id)
            ->where('event_type', $event)
            ->where('channel', $channel)
            ->where('created_at', '>=', now()->subMinutes($withinMinutes));

        // For negotiation events, include item_id in duplicate check
        if (str_starts_with($event, 'negotiation_') && isset($context['item']?->id)) {
            $query->whereJsonContains('meta', ['item_id' => $context['item']->id]);
        }

        // For receipt events, include receipt_id
        if (in_array($event, ['receipt_recorded', 'receipt_verified', 'receipt_issue_resolved'], true)
            && isset($context['receipt_id'])) {
            $query->whereJsonContains('meta', ['receipt_id' => $context['receipt_id']]);
        }

        // For delivery events, include delivery_id
        if ($event === 'delivery_recorded' && isset($context['delivery_id'])) {
            $query->whereJsonContains('meta', ['delivery_id' => $context['delivery_id']]);
        }

        // For document generation, include document_type (dan document_id bila ada)
        // agar penerbitan dua dokumen berbeda dalam <5 menit tidak terblokir.
        if ($event === 'document_generated' && isset($context['document_type'])) {
            $query->whereJsonContains('meta', ['document_type' => $context['document_type']]);

            if (isset($context['document_id'])) {
                $query->whereJsonContains('meta', ['document_id' => $context['document_id']]);
            }
        }

        return $query->exists();
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
        ?User $triggeredBy = null,
        array $context = []
    ): ?Notification {
        if ($this->isDuplicate($request, $event, $channel, $context)) {
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
            'meta' => array_filter([
                'item_id' => $context['item']?->id ?? null,
                'negotiation_id' => $context['negotiation']?->id ?? null,
                'receipt_id' => $context['receipt_id'] ?? null,
                'delivery_id' => $context['delivery_id'] ?? null,
                'document_type' => $context['document_type'] ?? null,
                'document_id' => $context['document_id'] ?? null,
                'offered_by' => $context['offered_by'] ?? null,
                'decided_by' => $context['decided_by'] ?? null,
            ]),
        ]);

        // Observer akan dispatch queue job otomatis

        return $notification;
    }

    /**
     * Build message berdasarkan event type.
     */
    private function buildMessage(ProcurementRequest $request, string $event, array $context, ?User $triggeredBy = null): ?string
    {
        return match ($event) {
            'submitted' => NotificationContentBuilder::buildSubmitted($request),
            'verified' => NotificationContentBuilder::buildVerified($request),
            'rejected' => NotificationContentBuilder::buildRejected($request, $context['reason'] ?? ''),
            'needs_revision' => NotificationContentBuilder::buildNeedsRevision(
                $request,
                $context['reason'] ?? ''
            ),
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
            'receipt_verified' => NotificationContentBuilder::buildReceiptVerified($request, $context),
            'record_payment' => NotificationContentBuilder::buildPaymentRecorded(
                $request,
                $context['amount'],
                $context['method'],
                $context['reference'] ?? '',
                $context['paidAt'] ?? now()->toDateString()
            ),
            'confirm_payment' => NotificationContentBuilder::buildPaymentConfirmed(
                $request,
                $context['amount'],
                $context['receiptNumber'] ?? null
            ),
            'completed' => NotificationContentBuilder::buildCompleted($request),
            'document_generated' => NotificationContentBuilder::buildDocumentGenerated($request),
            'processing_started' => NotificationContentBuilder::buildProcessingStarted($request),
            'delivery_recorded' => NotificationContentBuilder::buildDeliveryRecorded($request, $context),
            'receipt_recorded' => NotificationContentBuilder::buildReceiptRecorded($request, $context),
            'receipt_issue_resolved' => NotificationContentBuilder::buildReceiptIssueResolved($request, $context),
            'price_agreed' => NotificationContentBuilder::buildPriceAgreed($request),
            'cancelled' => NotificationContentBuilder::buildCancelled($request, $context['reason'] ?? '', $triggeredBy),
            default => null,
        };
    }

    /**
     * Tentukan recipient phone berdasarkan event.
     * - Admin CV menerima: submitted, receipt_recorded, negotiation_offer (from school), negotiation_accepted/rejected (decided by school)
     * - School menerima: verified, rejected, supplier_assigned, negotiation_offer (from admin), negotiation_accepted/rejected (decided by admin), items_prepared, completed, receipt_verified, processing_started, delivery_recorded, receipt_issue_resolved, price_agreed
     */
    private function resolveRecipientPhone(ProcurementRequest $request, string $event, array $context = [], ?User $triggeredBy = null): ?string
    {
        // Pembatalan: notifikasikan pihak yang berlawanan dengan pembatal.
        if ($event === 'cancelled') {
            return ($triggeredBy?->isSchool() ?? false)
                ? ($this->adminCvPhone ?: null)
                : ($request->school->phone_number ?? null);
        }

        // Admin events: submitted, receipt_recorded
        $adminEvents = [
            'submitted',
            'receipt_recorded',
        ];

        // Check negotiation events - direction depends on who offered/decided
        if (in_array($event, ['negotiation_offer', 'negotiation_accepted', 'negotiation_rejected'], true)) {
            if ($event === 'negotiation_offer') {
                $offeredBy = $context['offered_by'] ?? ($context['negotiation']?->offered_by ?? null);
                if ($offeredBy === ProcurementNegotiation::OFFERED_BY_SCHOOL) {
                    return $this->adminCvPhone ?: null;
                }
                // Admin -> School falls through to school phone
            } else {
                // negotiation_accepted/rejected - check who decided
                $decidedBy = $context['decided_by'] ?? null;
                if ($decidedBy === 'school') {
                    return $this->adminCvPhone ?: null;
                }
                // Admin decided -> School falls through
            }
        }

        if (in_array($event, $adminEvents, true)) {
            return $this->adminCvPhone ?: null;
        }

        // All other events go to school
        return $request->school->phone_number ?? null;
    }

    /**
     * Tentukan recipient email berdasarkan event.
     */
    private function resolveRecipientEmail(ProcurementRequest $request, string $event, array $context = [], ?User $triggeredBy = null): ?string
    {
        // Pembatalan: notifikasikan pihak yang berlawanan dengan pembatal.
        if ($event === 'cancelled') {
            return ($triggeredBy?->isSchool() ?? false)
                ? ($this->adminCvEmail ?: null)
                : ($this->resolveSchoolEmail($request));
        }

        $adminEvents = [
            'submitted',
            'receipt_recorded',
        ];

        if (in_array($event, ['negotiation_offer', 'negotiation_accepted', 'negotiation_rejected'], true)) {
            if ($event === 'negotiation_offer') {
                $offeredBy = $context['offered_by'] ?? ($context['negotiation']?->offered_by ?? null);
                if ($offeredBy === ProcurementNegotiation::OFFERED_BY_SCHOOL) {
                    return $this->adminCvEmail ?: null;
                }
            } else {
                $decidedBy = $context['decided_by'] ?? null;
                if ($decidedBy === 'school') {
                    return $this->adminCvEmail ?: null;
                }
            }
        }

        if (in_array($event, $adminEvents, true)) {
            return $this->adminCvEmail ?: null;
        }

        // School event: gunakan email sekolah, fallback ke akun user sekolah aktif.
        return $this->resolveSchoolEmail($request);
    }

    /**
     * Email sekolah dengan fallback ke akun user sekolah aktif.
     */
    private function resolveSchoolEmail(ProcurementRequest $request): ?string
    {
        $schoolEmail = $request->school->email ?? null;

        if (filled($schoolEmail)) {
            return $schoolEmail;
        }

        return $request->school?->account?->email ?? null;
    }
}
