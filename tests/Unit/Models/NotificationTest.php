<?php

use App\Models\Notification;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->create($overrides));
}

function createPendingNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->pending()->create($overrides));
}

function createSentNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->sent()->create($overrides));
}

function createFailedNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->failed()->create($overrides));
}

test('notification belongs to procurement request', function () {
    $notification = createNotification([
        'procurement_request_id' => ProcurementRequest::factory(),
    ]);

    expect($notification->procurementRequest)->toBeInstanceOf(ProcurementRequest::class);
});

test('notification has correct status constants', function () {
    expect(Notification::STATUS_PENDING)->toBe('pending');
    expect(Notification::STATUS_SENT)->toBe('sent');
    expect(Notification::STATUS_FAILED)->toBe('failed');
});

test('notification has correct channel constants', function () {
    expect(Notification::CHANNEL_WHATSAPP)->toBe('whatsapp');
    expect(Notification::CHANNEL_EMAIL)->toBe('email');
});

test('markSent sets status and sent_at', function () {
    $notification = createPendingNotification();

    $notification->markSent();

    expect($notification->fresh()->status)->toBe(Notification::STATUS_SENT);
    expect($notification->fresh()->sent_at)->not->toBeNull();
    expect($notification->fresh()->error_message)->toBeNull();
});

test('markFailed sets status and error message', function () {
    $notification = createPendingNotification();

    $notification->markFailed('Connection timeout');

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('Connection timeout');
});

test('isPending returns true when status is pending', function () {
    $notification = createPendingNotification();

    expect($notification->isPending())->toBeTrue();
    expect($notification->isSent())->toBeFalse();
    expect($notification->isFailed())->toBeFalse();
});

test('isSent returns true when status is sent', function () {
    $notification = createSentNotification();

    expect($notification->isSent())->toBeTrue();
    expect($notification->isPending())->toBeFalse();
    expect($notification->isFailed())->toBeFalse();
});

test('isFailed returns true when status is failed', function () {
    $notification = createFailedNotification();

    expect($notification->isFailed())->toBeTrue();
    expect($notification->isPending())->toBeFalse();
    expect($notification->isSent())->toBeFalse();
});

test('isWhatsapp returns true when channel is whatsapp', function () {
    $notification = createNotification(['channel' => Notification::CHANNEL_WHATSAPP]);

    expect($notification->isWhatsapp())->toBeTrue();
    expect($notification->isEmail())->toBeFalse();
});

test('isEmail returns true when channel is email', function () {
    $notification = createNotification(['channel' => Notification::CHANNEL_EMAIL]);

    expect($notification->isEmail())->toBeTrue();
    expect($notification->isWhatsapp())->toBeFalse();
});

test('scope byChannel filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->whatsapp()->count(3)->create());
    Notification::withoutEvents(fn () => Notification::factory()->email()->count(2)->create());

    expect(Notification::byChannel(Notification::CHANNEL_WHATSAPP)->count())->toBe(3);
    expect(Notification::byChannel(Notification::CHANNEL_EMAIL)->count())->toBe(2);
});

test('scope pending filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->pending()->count(2)->create());
    Notification::withoutEvents(fn () => Notification::factory()->sent()->count(3)->create());

    expect(Notification::pending()->count())->toBe(2);
});

test('scope sent filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->pending()->count(2)->create());
    Notification::withoutEvents(fn () => Notification::factory()->sent()->count(3)->create());

    expect(Notification::sent()->count())->toBe(3);
});

test('scope failed filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->pending()->count(2)->create());
    Notification::withoutEvents(fn () => Notification::factory()->failed()->count(1)->create());

    expect(Notification::failed()->count())->toBe(1);
});

test('scope forProcurement filters correctly', function () {
    $pr = ProcurementRequest::factory()->create();
    Notification::withoutEvents(fn () => Notification::factory()->count(3)->create(['procurement_request_id' => $pr->id]));
    Notification::withoutEvents(fn () => Notification::factory()->count(2)->create());

    expect(Notification::forProcurement($pr->id)->count())->toBe(3);
});

test('status label attribute returns correct label', function () {
    $pending = createPendingNotification();
    $sent = createSentNotification();
    $failed = createFailedNotification();

    expect($pending->status_label)->toBe('Menunggu');
    expect($sent->status_label)->toBe('Terkirim');
    expect($failed->status_label)->toBe('Gagal');
});

test('channel label attribute returns correct label', function () {
    $whatsapp = createNotification(['channel' => Notification::CHANNEL_WHATSAPP]);
    $email = createNotification(['channel' => Notification::CHANNEL_EMAIL]);

    expect($whatsapp->channel_label)->toBe('WhatsApp');
    expect($email->channel_label)->toBe('Email');
});

test('notification can be created with all fields', function () {
    $notification = createNotification([
        'procurement_request_id' => ProcurementRequest::factory(),
        'channel' => Notification::CHANNEL_WHATSAPP,
        'recipient' => '6281234567890',
        'message_content' => 'Test message',
        'status' => Notification::STATUS_SENT,
        'sent_at' => now(),
    ]);

    expect($notification->procurement_request_id)->not->toBeNull();
    expect($notification->channel)->toBe(Notification::CHANNEL_WHATSAPP);
    expect($notification->recipient)->toBe('6281234567890');
    expect($notification->message_content)->toBe('Test message');
    expect($notification->status)->toBe(Notification::STATUS_SENT);
    expect($notification->sent_at)->not->toBeNull();
});

test('notification can store event_type', function () {
    $notification = createNotification([
        'event_type' => 'submitted',
    ]);

    expect($notification->event_type)->toBe('submitted');
});

test('notification can store triggered_by', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $notification = createNotification([
        'triggered_by' => $user->id,
    ]);

    expect($notification->triggered_by)->toBe($user->id);
    expect($notification->triggeredBy)->toBeInstanceOf(User::class);
});

test('notification triggeredBy returns null when not set', function () {
    $notification = createNotification([
        'triggered_by' => null,
    ]);

    expect($notification->triggeredBy)->toBeNull();
});

test('scope byEventType filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->count(3)->create(['event_type' => 'submitted']));
    Notification::withoutEvents(fn () => Notification::factory()->count(2)->create(['event_type' => 'verified']));

    expect(Notification::byEventType('submitted')->count())->toBe(3);
    expect(Notification::byEventType('verified')->count())->toBe(2);
});

test('markAsRead sets read_at', function () {
    $notification = createNotification();

    $notification->markAsRead();

    expect($notification->fresh()->read_at)->not->toBeNull();
    expect($notification->fresh()->isRead())->toBeTrue();
});

test('markAsUnread clears read_at', function () {
    $notification = createNotification(['read_at' => now()]);

    $notification->markAsUnread();

    expect($notification->fresh()->read_at)->toBeNull();
    expect($notification->fresh()->isRead())->toBeFalse();
});

test('isRead returns true when read_at is set', function () {
    $notification = createNotification(['read_at' => now()]);

    expect($notification->isRead())->toBeTrue();
});

test('isRead returns false when read_at is null', function () {
    $notification = createNotification();

    expect($notification->isRead())->toBeFalse();
});

test('scope unread filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->count(3)->create());
    Notification::withoutEvents(fn () => Notification::factory()->count(2)->create(['read_at' => now()]));

    expect(Notification::unread()->count())->toBe(3);
});

test('scope read filters correctly', function () {
    Notification::withoutEvents(fn () => Notification::factory()->count(3)->create());
    Notification::withoutEvents(fn () => Notification::factory()->count(2)->create(['read_at' => now()]));

    expect(Notification::read()->count())->toBe(2);
});
