<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('webhook endpoint accepts POST request', function () {
    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'message',
        'session' => 'default',
        'payload' => [
            'from' => '6281234567890@c.us',
            'body' => 'Hello',
        ],
    ]);

    $response->assertOk();
});

test('webhook handles message event', function () {
    Log::shouldReceive('info')
        ->with('WhatsApp webhook received', \PHPUnit\Framework\Assert::anything())
        ->once();
    Log::shouldReceive('info')
        ->with('WhatsApp message received', \PHPUnit\Framework\Assert::anything())
        ->once();

    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'message',
        'session' => 'default',
        'payload' => [
            'from' => '6281234567890@c.us',
            'body' => 'Hello',
        ],
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ok']);
});

test('webhook handles session.status event', function () {
    Log::shouldReceive('info')
        ->with('WhatsApp webhook received', \PHPUnit\Framework\Assert::anything())
        ->once();
    Log::shouldReceive('info')
        ->with('WhatsApp session status changed', \PHPUnit\Framework\Assert::anything())
        ->once();

    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'session.status',
        'session' => 'default',
        'payload' => [
            'status' => 'WORKING',
        ],
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ok']);
});

test('webhook ignores unknown events', function () {
    Log::shouldReceive('info')
        ->with('WhatsApp webhook received', \PHPUnit\Framework\Assert::anything())
        ->once();

    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'unknown.event',
        'session' => 'default',
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ignored']);
});
