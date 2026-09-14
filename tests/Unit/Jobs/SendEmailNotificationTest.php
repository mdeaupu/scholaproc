<?php

use App\Jobs\SendEmailNotification;
use App\Models\Notification;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createEmailNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->create($overrides));
}

test('send email notification job is queued on notifications queue', function () {
    $notification = createEmailNotification([
        'channel' => Notification::CHANNEL_EMAIL,
    ]);

    $job = new SendEmailNotification($notification);

    expect($job->queue)->toBe('notifications');
});

test('send email notification job has correct retry settings', function () {
    $notification = createEmailNotification([
        'channel' => Notification::CHANNEL_EMAIL,
    ]);

    $job = new SendEmailNotification($notification);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(60);
});

test('send email notification job handles successful send', function () {
    $notification = createEmailNotification([
        'channel' => Notification::CHANNEL_EMAIL,
        'status' => Notification::STATUS_PENDING,
    ]);

    $email = \Mockery::mock(EmailService::class);
    $email->shouldReceive('send')
        ->once()
        ->andReturn(['success' => true, 'error' => null]);

    $job = new SendEmailNotification($notification);
    $job->handle($email);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_SENT);
    expect($notification->fresh()->sent_at)->not->toBeNull();
});

test('send email notification job handles failed send', function () {
    $notification = createEmailNotification([
        'channel' => Notification::CHANNEL_EMAIL,
        'status' => Notification::STATUS_PENDING,
    ]);

    $email = \Mockery::mock(EmailService::class);
    $email->shouldReceive('send')
        ->once()
        ->andReturn(['success' => false, 'error' => 'SMTP connection failed']);

    $job = new SendEmailNotification($notification);
    $job->handle($email);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('SMTP connection failed');
});

test('send email notification job failed method marks notification as failed', function () {
    $notification = createEmailNotification([
        'channel' => Notification::CHANNEL_EMAIL,
        'status' => Notification::STATUS_PENDING,
    ]);

    $job = new SendEmailNotification($notification);
    $exception = new \Exception('Mail error');
    $job->failed($exception);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('Mail error');
});
