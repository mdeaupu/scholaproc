<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppService
{
    private string $baseUrl;

    private string $session;

    private string $apiKey;

    private int $rateLimit;

    // Sesi WAHA aktif oleh server WAHA tetapi belum diinisialisasi (HTTP 404).
    public const STATUS_NOT_FOUND = 'NOT_FOUND';

    public function __construct()
    {
        $this->baseUrl = config('waha.base_url', 'http://localhost:3000');
        $this->session = config('waha.session', 'default');
        $this->apiKey = config('waha.api_key', '');
        $this->rateLimit = config('waha.rate_limit', 30);
    }

    public function send(string $phone, string $message): array
    {
        $phone = $this->formatPhoneNumber($phone);

        $limiterKey = 'whatsapp-sends';

        if (RateLimiter::tooManyAttempts($limiterKey, $this->rateLimit)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            Log::warning('WhatsApp rate limit reached', [
                'phone' => $phone,
                'retry_after' => $seconds,
            ]);

            return ['success' => false, 'error' => "Rate limit exceeded. Try again in {$seconds} seconds."];
        }

        RateLimiter::hit($limiterKey, 60);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/api/sendText", [
                    'session' => $this->session,
                    'chatId' => "{$phone}@c.us",
                    'text' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['id'] ?? ($data['_data']['id'] ?? null);

                return ['success' => true, 'error' => null, 'message_id' => $messageId];
            }

            $error = $response->json('error', $response->body());

            Log::error('WhatsApp send failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'error' => $error,
            ]);

            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        return $phone;
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/sessions/{$this->session}");

            if ($response->successful()) {
                $data = $response->json();

                return ($data['status'] ?? '') === 'WORKING';
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSessionStatus(): ?string
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/sessions/{$this->session}");

            if ($response->successful()) {
                return $response->json('status');
            }

            // WAHA aktif tapi session belum diinisialisasi (HTTP 404).
            if ($response->status() === 404) {
                return self::STATUS_NOT_FOUND;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function startSession(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/api/sessions/{$this->session}/start");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            // WAHA melaporkan session belum terdaftar (HTTP 404).
            // Coba buat session baru lewat endpoint pembuatan session WAHA.
            if ($response->status() === 404) {
                return $this->createSession();
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Buat session WhatsApp baru di WAHA (fallback saat start mengembalikan 404).
     */
    private function createSession(): array
    {
        try {
            $payload = [
                'name' => $this->session,
                'engine' => config('waha.engine', 'NOWEB'),
                'start' => true,
            ];

            $webhookUrl = config('waha.webhook_url');
            if ($webhookUrl) {
                $events = array_filter(explode(',', config('waha.webhook_events', 'message,message.ack,session.status')));
                $payload['webhook'] = [
                    'url' => $webhookUrl,
                    'events' => array_values($events),
                ];

                $webhookToken = config('waha.webhook_token');
                if ($webhookToken) {
                    $payload['webhook']['token'] = $webhookToken;
                }
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/api/sessions", $payload);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getQRCode(): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/{$this->session}/auth/qr?format=image");

            if ($response->successful()) {
                $body = $response->body();
                if (str_starts_with($body, 'data:image')) {
                    return $body;
                }

                return 'data:image/png;base64,'.base64_encode($body);
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('WhatsApp QR code fetch failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function stopSession(): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/api/sessions/{$this->session}/stop");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function logout(): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/api/sessions/{$this->session}/logout");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteSession(): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->delete("{$this->baseUrl}/api/sessions/{$this->session}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getSessionInfo(): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/sessions/{$this->session}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
