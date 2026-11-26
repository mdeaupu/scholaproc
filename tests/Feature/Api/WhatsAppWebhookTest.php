<?php

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Assert;

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
        ->with('WhatsApp webhook received', Assert::anything())
        ->once();
    Log::shouldReceive('info')
        ->with('WhatsApp message received', Assert::anything())
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
        ->with('WhatsApp webhook received', Assert::anything())
        ->once();
    Log::shouldReceive('info')
        ->with('WhatsApp session status changed', Assert::anything())
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

test('webhook message.ack delivered marks whatsapp notification sent', function () {
    $notification = Notification::factory()->create([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'status' => Notification::STATUS_PENDING,
        'recipient' => '6281234567890',
        'event_type' => 'submitted',
        'message_content' => 'Test',
    ]);

    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'message.ack',
        'session' => 'default',
        'payload' => [
            'id' => 'msg_123',
            'ack' => 'delivered',
            'to' => '6281234567890@c.us',
        ],
    ]);

    $response->assertOk()->assertJson(['status' => 'ok']);
    expect($notification->fresh()->status)->toBe(Notification::STATUS_SENT);
    expect($notification->fresh()->sent_at)->not->toBeNull();
});

test('webhook message.ack failed marks whatsapp notification failed', function () {
    $notification = Notification::factory()->create([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'status' => Notification::STATUS_PENDING,
        'recipient' => '6281234567890',
        'event_type' => 'submitted',
        'message_content' => 'Test',
    ]);

    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'message.ack',
        'session' => 'default',
        'payload' => [
            'id' => 'msg_124',
            'ack' => 'failed',
            'to' => '6281234567890@c.us',
        ],
    ]);

    $response->assertOk()->assertJson(['status' => 'ok']);
    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toContain('delivery ack: failed');
});

test('webhook message.ack for unknown recipient is ignored without error', function () {
    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'message.ack',
        'session' => 'default',
        'payload' => [
            'id' => 'msg_125',
            'ack' => 'delivered',
            'to' => '6289999999999@c.us',
        ],
    ]);

    $response->assertOk()->assertJson(['status' => 'ok']);
});

test('webhook ignores unknown events', function () {
    Log::shouldReceive('info')
        ->with('WhatsApp webhook received', Assert::anything())
        ->once();

    $response = $this->postJson('/api/whatsapp/webhook', [
        'event' => 'unknown.event',
        'session' => 'default',
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ignored']);
});
