<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return match ($event) {
            'message' => $this->handleMessage($payload),
            'session.status' => $this->handleSessionStatus($payload),
            default => response()->json(['status' => 'ignored']),
        };
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

    private function handleSessionStatus(array $payload): JsonResponse
    {
        $status = $payload['payload']['status'] ?? null;

        Log::info('WhatsApp session status changed', [
            'status' => $status,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
