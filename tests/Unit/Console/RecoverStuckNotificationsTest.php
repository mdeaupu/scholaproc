<?php

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('recover retries stuck notifications', function () {
    Queue::fake();

    // Create stuck notification (20 minutes old)
    Notification::withoutEvents(fn () => Notification::factory()->pending()->create([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'created_at' => now()->subMinutes(20),
        'meta' => ['retry_count' => 0],
    ]));

    $this->artisan('notifications:recover', ['--minutes' => '10'])
        ->expectsOutputToContain('Recovery selesai: 1 notifikasi diretry')
        ->assertExitCode(0);

    Queue::assertPushed(SendWhatsAppNotification::class, 1);
});

test('recover force-fails notifications that exceed max retries', function () {
    Queue::fake();

    // Create stuck notification with max retries exceeded
    Notification::withoutEvents(fn () => Notification::factory()->pending()->create([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'created_at' => now()->subMinutes(20),
        'meta' => ['retry_count' => 3],
    ]));

    $this->artisan('notifications:recover', ['--minutes' => '10', '--max-retries' => '3'])
        ->expectsOutputToContain('Recovery selesai: 0 notifikasi diretry, 1 notifikasi di-force-fail')
        ->assertExitCode(0);

    $notification = Notification::first();
    expect($notification->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->error_message)->toContain('exceeded max retries');
});

test('recover does not touch recent notifications', function () {
    Queue::fake();

    // Create recent notification (5 minutes old)
    Notification::withoutEvents(fn () => Notification::factory()->pending()->create([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'created_at' => now()->subMinutes(5),
        'meta' => ['retry_count' => 0],
    ]));

    $this->artisan('notifications:recover', ['--minutes' => '10'])
        ->expectsOutputToContain('Recovery selesai: 0 notifikasi diretry, 0 notifikasi di-force-fail')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('recover increments retry count', function () {
    Queue::fake();

    Notification::withoutEvents(fn () => Notification::factory()->pending()->create([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'created_at' => now()->subMinutes(20),
        'meta' => ['retry_count' => 1],
    ]));

    $this->artisan('notifications:recover', ['--minutes' => '10'])
        ->assertExitCode(0);

    $notification = Notification::first();
    expect($notification->meta['retry_count'])->toBe(2);
});

test('recover handles email channel notifications', function () {
    Queue::fake();

    Notification::withoutEvents(fn () => Notification::factory()->pending()->create([
        'channel' => Notification::CHANNEL_EMAIL,
        'created_at' => now()->subMinutes(20),
        'meta' => ['retry_count' => 0],
    ]));

    $this->artisan('notifications:recover', ['--minutes' => '10'])
        ->expectsOutputToContain('Recovery selesai: 1 notifikasi diretry')
        ->assertExitCode(0);

    Queue::assertPushed(SendEmailNotification::class, 1);
});
