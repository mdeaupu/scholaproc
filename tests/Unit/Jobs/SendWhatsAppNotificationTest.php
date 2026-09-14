<?php

use App\Jobs\SendWhatsAppNotification;
use App\Models\Notification;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createWaNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->create($overrides));
}

test('send whatsapp notification job handles successful send', function () {
    $notification = createWaNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'status' => Notification::STATUS_PENDING,
    ]);

    $whatsapp = \Mockery::mock(WhatsAppService::class);
    $whatsapp->shouldReceive('send')
        ->once()
        ->with($notification->recipient, $notification->message_content)
        ->andReturn(['success' => true, 'error' => null]);

    $job = new SendWhatsAppNotification($notification);
    $job->handle($whatsapp);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_SENT);
    expect($notification->fresh()->sent_at)->not->toBeNull();
});

test('send whatsapp notification job handles failed send', function () {
    $notification = createWaNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'status' => Notification::STATUS_PENDING,
    ]);

    $whatsapp = \Mockery::mock(WhatsAppService::class);
    $whatsapp->shouldReceive('send')
        ->once()
        ->andReturn(['success' => false, 'error' => 'Phone not found']);

    $job = new SendWhatsAppNotification($notification);
    $job->handle($whatsapp);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('Phone not found');
});

test('send whatsapp notification job handles exception in failed method', function () {
    $notification = createWaNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'status' => Notification::STATUS_PENDING,
    ]);

    $job = new SendWhatsAppNotification($notification);
    $exception = new \Exception('Something went wrong');
    $job->failed($exception);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('Something went wrong');
});

test('send whatsapp notification job is queued on notifications queue', function () {
    $notification = createWaNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
    ]);

    $job = new SendWhatsAppNotification($notification);

    expect($job->queue)->toBe('notifications');
});

test('send whatsapp notification job has correct retry settings', function () {
    $notification = createWaNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
    ]);

    $job = new SendWhatsAppNotification($notification);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(30);
});
