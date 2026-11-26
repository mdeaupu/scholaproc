<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $session = $request->input('session');
        $payload = $request->all();

        Log::info('WhatsApp webhook received', [
            'event' => $event,
            'session' => $session,
        ]);

        if ($event === 'message') {
            return $this->handleMessage($payload);
        }

        if ($event === 'message.ack') {
            return $this->handleDeliveryStatus($payload);
        }

        if ($event === 'session.status') {
            return $this->handleSessionStatus($payload);
        }

        return response()->json(['status' => 'ignored']);
    }

    private function handleMessage(array $payload): JsonResponse
    {
        $from = $payload['payload']['from'] ?? null;
        $body = $payload['payload']['body'] ?? null;

        if ($from && $body) {
            Log::info('WhatsApp message received', [
                'from' => $from,
                'body' => $body,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleDeliveryStatus(array $payload): JsonResponse
    {
        try {
            $inner = $payload['payload'] ?? [];
            $ack = $inner['ack'] ?? null;
            $to = $inner['to'] ?? null;
            $messageId = $payload['id'] ?? ($inner['messageId'] ?? null);

            if (! $ack) {
                return response()->json(['status' => 'ignored']);
            }

            $notification = null;

            // Utamakan pencocokan via ID pesan WAHA yang tersimpan di meta.
            if ($messageId) {
                $notification = Notification::where('channel', Notification::CHANNEL_WHATSAPP)
                    ->where('meta->waha_message_id', $messageId)
                    ->latest('created_at')
                    ->first();
            }

            // Fallback: cocokkan berdasarkan nomor tujuan + status aktif.
            if (! $notification && $to) {
                $phone = preg_replace('/[^0-9]/', '', (string) $to);
                if (str_starts_with($phone, '0')) {
                    $phone = '62'.substr($phone, 1);
                }

                $notification = Notification::where('channel', Notification::CHANNEL_WHATSAPP)
                    ->where('recipient', $phone)
                    ->whereIn('status', [Notification::STATUS_PENDING, Notification::STATUS_SENT, Notification::STATUS_FAILED])
                    ->orderByDesc('created_at')
                    ->first();
            }

            if ($notification) {
                if (in_array($ack, ['delivered', 'read'], true)) {
                    $notification->markSent();
                } elseif (in_array($ack, ['failed', 'error'], true)) {
                    $notification->markFailed('WhatsApp delivery ack: '.$ack);
                }
            }

            Log::info('WhatsApp delivery ack processed', [
                'message_id' => $messageId,
                'to' => $to,
                'ack' => $ack,
                'matched' => $notification?->id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Gagal memproses delivery status WhatsApp', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleSessionStatus(array $payload): JsonResponse
    {
        $status = $payload['payload']['status'] ?? null;

        Log::info('WhatsApp session status changed', [
            'status' => $status,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
