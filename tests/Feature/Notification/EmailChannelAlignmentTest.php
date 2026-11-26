<?php

use App\Jobs\SendEmailNotification;
use App\Mail\ProcurementNotification;
use App\Models\Notification;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\Subsidiary;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function makeSchoolWithEmail(?string $email = 'sekolah@test.com', ?string $accountEmail = null): School
{
    $school = School::factory()->create(['email' => $email]);

    if ($accountEmail !== null) {
        User::factory()->create([
            'role' => 'school',
            'school_id' => $school->id,
            'email' => $accountEmail,
        ]);
    }

    return $school;
}

function makeEmailNotification(School $school, array $overrides = []): Notification
{
    $pr = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    return Notification::create(array_merge([
        'procurement_request_id' => $pr->id,
        'event_type' => 'verified',
        'channel' => Notification::CHANNEL_EMAIL,
        'recipient' => 'sekolah@test.com',
        'message_content' => 'Notifikasi uji',
        'status' => Notification::STATUS_PENDING,
    ], $overrides));
}

test('sync dispatch success marks notification sent', function () {
    config(['mail.sync' => true]);
    Mail::fake();

    $school = makeSchoolWithEmail();
    $notification = makeEmailNotification($school);

    Mail::assertSent(ProcurementNotification::class);
    expect($notification->fresh()->status)->toBe(Notification::STATUS_SENT);
});

test('sync dispatch failure marks notification failed without throwing', function () {
    config(['mail.sync' => true]);

    $school = makeSchoolWithEmail();
    $notification = makeEmailNotification($school);

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP connection failed'));

    expect(fn () => SendEmailNotification::dispatchSync($notification))
        ->not->toThrow(Throwable::class);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('SMTP connection failed');
});

test('queue dispatch pushes job onto default queue when sync disabled', function () {
    config(['mail.sync' => false]);
    Queue::fake();

    $school = makeSchoolWithEmail();
    makeEmailNotification($school);

    Queue::assertPushed(SendEmailNotification::class, function (SendEmailNotification $job) {
        return $job->queue === 'default';
    });
});

test('retry works for pending and failed email notifications in sync mode', function () {
    config(['mail.sync' => true]);
    Mail::fake();

    $school = makeSchoolWithEmail();
    $pr = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    $pending = Notification::withoutEvents(fn () => Notification::factory()->pending()->email()->create([
        'procurement_request_id' => $pr->id,
        'recipient' => 'sekolah@test.com',
    ]));
    $failed = Notification::withoutEvents(fn () => Notification::factory()->failed()->email()->create([
        'procurement_request_id' => $pr->id,
        'recipient' => 'sekolah@test.com',
    ]));

    $pending->retry();
    $failed->retry();

    expect($pending->fresh()->status)->toBe(Notification::STATUS_SENT);
    expect($failed->fresh()->status)->toBe(Notification::STATUS_SENT);
    Mail::assertSent(ProcurementNotification::class, 2);
});

test('retry marks failed when recipient email invalid', function () {
    config(['mail.sync' => true]);
    Mail::fake();

    $school = makeSchoolWithEmail();
    $pr = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    $notification = Notification::withoutEvents(fn () => Notification::factory()->failed()->email()->create([
        'procurement_request_id' => $pr->id,
        'recipient' => 'not-an-email',
    ]));

    $notification->retry();

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($notification->fresh()->error_message)->toBe('Email penerima tidak tersedia atau tidak valid');
    Mail::assertNothingSent();
});

test('resolveRecipientEmail falls back to school account email', function () {
    config(['waha.admin_cv_email' => 'admin@cv.com']);
    $school = makeSchoolWithEmail(null, 'akun.sekolah@test.com');
    $pr = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    $service = new NotificationService;
    $service->notify($pr, 'document_generated');

    $email = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_EMAIL)->first());

    expect($email)->not->toBeNull();
    expect($email->recipient)->toBe('akun.sekolah@test.com');
});

test('empty recipient for school event persists failed email notification without dispatch', function () {
    config(['mail.sync' => true]);
    config(['waha.admin_cv_email' => 'admin@cv.com']);
    Mail::fake();

    $school = makeSchoolWithEmail(null);
    $pr = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    $service = new NotificationService;
    $service->notify($pr, 'verified');

    $email = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_EMAIL)->first());

    expect($email)->not->toBeNull();
    expect($email->status)->toBe(Notification::STATUS_FAILED);
    expect($email->recipient)->toBe('-');
    expect($email->error_message)->toBe('Email penerima tidak tersedia atau tidak valid');
    Mail::assertNothingSent();
});

test('mailable content renders anak perusahaan and no supplier term', function () {
    $school = makeSchoolWithEmail();
    $subsidiary = Subsidiary::factory()->create(['name' => 'PT Anak Perusahaan Maju']);
    $pr = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'subsidiary_id' => $subsidiary->id,
        'status' => ProcurementRequest::STATUS_PROCESSING,
    ]);

    $notification = Notification::withoutEvents(fn () => Notification::factory()->email()->create([
        'procurement_request_id' => $pr->id,
        'event_type' => 'processing_started',
        'recipient' => 'sekolah@test.com',
        'message_content' => 'Pemrosesan telah dimulai.',
    ]));

    $mailable = new ProcurementNotification(
        notification: $notification,
        procurementRequest: $pr,
        subjectLine: '[Scholaproc] Pemrosesan Dimulai — #'.$pr->id.' ('.$school->name.')',
        messageBody: 'Pemrosesan telah dimulai.',
        documents: [],
    );

    $html = $mailable->render();

    expect($html)->toContain('Anak Perusahaan');
    expect($html)->toContain('PT Anak Perusahaan Maju');
    expect($html)->toContain('Pantau Pengiriman Barang');
    expect(stripos($html, 'Supplier'))->toBeFalse();
});

test('transaction status is not rolled back when mailer fails', function () {
    config(['mail.sync' => true]);

    $school = makeSchoolWithEmail();
    $pr = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    $notification = makeEmailNotification($school, ['event_type' => 'verified']);

    expect($notification->fresh()->status)->toBe(Notification::STATUS_FAILED);
    expect($pr->fresh()->status)->toBe(ProcurementRequest::STATUS_VERIFIED);
});
