<?php

use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['waha.base_url' => 'http://localhost:3000']);
    config(['waha.session' => 'default']);
    config(['waha.api_key' => 'test-api-key']);
});

test('formatPhoneNumber converts 08xxx to 628xxx', function () {
    $service = new WhatsAppService();

    expect($service->formatPhoneNumber('081234567890'))->toBe('6281234567890');
    expect($service->formatPhoneNumber('0856789012345'))->toBe('62856789012345');
});

test('formatPhoneNumber keeps 62xxx unchanged', function () {
    $service = new WhatsAppService();

    expect($service->formatPhoneNumber('6281234567890'))->toBe('6281234567890');
});

test('formatPhoneNumber strips non-numeric characters', function () {
    $service = new WhatsAppService();

    expect($service->formatPhoneNumber('0812-345-67890'))->toBe('6281234567890');
    expect($service->formatPhoneNumber('+62 812 345 67890'))->toBe('6281234567890');
});

test('formatPhoneNumber returns as-is for unknown format', function () {
    $service = new WhatsAppService();

    expect($service->formatPhoneNumber('123456789'))->toBe('123456789');
});

test('send returns success on successful API response', function () {
    Http::fake([
        'http://localhost:3000/api/sendText' => Http::response(['key' => 'value'], 200),
    ]);

    $service = new WhatsAppService();
    $result = $service->send('081234567890', 'Hello World');

    expect($result['success'])->toBeTrue();
    expect($result['error'])->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'http://localhost:3000/api/sendText'
            && $request->hasHeader('X-API-KEY', 'test-api-key')
            && str_contains($request->body(), '6281234567890@c.us')
            && str_contains($request->body(), 'Hello World');
    });
});

test('send returns error on failed API response', function () {
    Http::fake([
        'http://localhost:3000/api/sendText' => Http::response(['error' => 'Phone not found'], 404),
    ]);

    $service = new WhatsAppService();
    $result = $service->send('081234567890', 'Hello');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Phone not found');
});

test('send returns error on exception', function () {
    Http::fake([
        'http://localhost:3000/api/sendText' => Http::response('', 500),
    ]);

    $service = new WhatsAppService();
    $result = $service->send('081234567890', 'Hello');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('isAvailable returns true when session status is WORKING', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
        ], 200),
    ]);

    $service = new WhatsAppService();

    expect($service->isAvailable())->toBeTrue();
});

test('isAvailable returns false when session status is not WORKING', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'SCAN_QR_CODE',
        ], 200),
    ]);

    $service = new WhatsAppService();

    expect($service->isAvailable())->toBeFalse();
});

test('isAvailable returns false on API failure', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([], 500),
    ]);

    $service = new WhatsAppService();

    expect($service->isAvailable())->toBeFalse();
});

test('isAvailable returns false on exception', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => function () {
            throw new \Exception('Connection refused');
        },
    ]);

    $service = new WhatsAppService();

    expect($service->isAvailable())->toBeFalse();
});

test('getSessionStatus returns status string', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
        ], 200),
    ]);

    $service = new WhatsAppService();

    expect($service->getSessionStatus())->toBe('WORKING');
});

test('getSessionStatus returns null on failure', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([], 500),
    ]);

    $service = new WhatsAppService();

    expect($service->getSessionStatus())->toBeNull();
});

test('startSession returns success on successful start', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default/start' => Http::response([
            'status' => 'STARTING',
        ], 200),
    ]);

    $service = new WhatsAppService();
    $result = $service->startSession();

    expect($result['success'])->toBeTrue();
    expect($result['data'])->toBeArray();
});

test('startSession returns error on failure', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default/start' => Http::response('Error', 500),
    ]);

    $service = new WhatsAppService();
    $result = $service->startSession();

    expect($result['success'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('stopSession returns success on successful stop', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default/stop' => Http::response([
            'status' => 'STOPPED',
        ], 200),
    ]);

    $service = new WhatsAppService();
    $result = $service->stopSession();

    expect($result['success'])->toBeTrue();
    expect($result['data'])->toBeArray();
});

test('stopSession returns error on failure', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default/stop' => Http::response('Error', 500),
    ]);

    $service = new WhatsAppService();
    $result = $service->stopSession();

    expect($result['success'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('getSessionInfo returns session data on success', function () {
    $sessionData = [
        'status' => 'WORKING',
        'me' => ['pushName' => 'My Phone'],
        'engine' => ['engine' => 'whatsapp-web.js'],
    ];

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response($sessionData, 200),
    ]);

    $service = new WhatsAppService();
    $result = $service->getSessionInfo();

    expect($result)->toBeArray();
    expect($result['status'])->toBe('WORKING');
    expect($result['me']['pushName'])->toBe('My Phone');
});

test('getSessionInfo returns null on failure', function () {
    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([], 500),
    ]);

    $service = new WhatsAppService();

    expect($service->getSessionInfo())->toBeNull();
});

test('getQRCode returns base64 image data', function () {
    $qrImage = base64_encode('fake-qr-image');

    Http::fake([
        'http://localhost:3000/api/default/auth/qr?format=image' => Http::response($qrImage, 200),
    ]);

    $service = new WhatsAppService();
    $result = $service->getQRCode();

    expect($result)->not->toBeNull();
    expect(str_starts_with($result, 'data:image/png;base64,'))->toBeTrue();
});

test('getQRCode returns data URI if already prefixed', function () {
    $dataUri = 'data:image/png;base64,fake-qr-image';

    Http::fake([
        'http://localhost:3000/api/default/auth/qr?format=image' => Http::response($dataUri, 200),
    ]);

    $service = new WhatsAppService();
    $result = $service->getQRCode();

    expect($result)->toBe($dataUri);
});

test('getQRCode returns null on failure', function () {
    Http::fake([
        'http://localhost:3000/api/default/auth/qr?format=image' => Http::response([], 500),
    ]);

    $service = new WhatsAppService();

    expect($service->getQRCode())->toBeNull();
});

test('getQRCode returns null on exception', function () {
    Http::fake([
        'http://localhost:3000/api/default/auth/qr?format=image' => function () {
            throw new \Exception('Timeout');
        },
    ]);

    $service = new WhatsAppService();

    expect($service->getQRCode())->toBeNull();
});

test('send respects rate limit', function () {
    config(['waha.rate_limit' => 2]);

    Http::fake([
        'http://localhost:3000/api/sendText' => Http::response(['key' => 'value'], 200),
    ]);

    $service = new WhatsAppService();

    // First two attempts should succeed
    $result1 = $service->send('081234567890', 'Message 1');
    expect($result1['success'])->toBeTrue();

    $result2 = $service->send('081234567890', 'Message 2');
    expect($result2['success'])->toBeTrue();

    // Third attempt should be rate limited
    $result3 = $service->send('081234567890', 'Message 3');
    expect($result3['success'])->toBeFalse();
    expect($result3['error'])->toContain('Rate limit');
});

test('send increments rate limiter counter', function () {
    config(['waha.rate_limit' => 30]);

    Http::fake([
        'http://localhost:3000/api/sendText' => Http::response(['key' => 'value'], 200),
    ]);

    $service = new WhatsAppService();
    $service->send('081234567890', 'Test');

    expect(RateLimiter::attempts('whatsapp-sends'))->toBeGreaterThan(0);
});
