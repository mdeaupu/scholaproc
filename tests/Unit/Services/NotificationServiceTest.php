<?php

use App\Models\Notification;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['waha.admin_cv_phone' => '08111222333']);
    config(['waha.admin_cv_email' => 'admin@cv.com']);
});

function createServicePrWithSchool(array $schoolOverrides = [], array $prOverrides = []): ProcurementRequest
{
    $school = School::factory()->create(array_merge([
        'phone_number' => '081234567890',
        'email' => 'sekolah@test.com',
    ], $schoolOverrides));

    return ProcurementRequest::factory()->create(array_merge([
        'school_id' => $school->id,
    ], $prOverrides));
}

test('notify creates whatsapp and email notifications for submitted event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'submitted');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());
    $email = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_EMAIL)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('08111222333');
    expect($whatsapp->message_content)->toContain('Pengajuan pengadaan baru');

    expect($email)->not->toBeNull();
    expect($email->recipient)->toBe('admin@cv.com');
});

test('notify sends to school for document_generated event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'document_generated');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());
    $email = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_EMAIL)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Dokumen pengadaan telah tersedia');

    expect($email)->not->toBeNull();
    expect($email->recipient)->toBe('sekolah@test.com');
});

test('notify sends to school for verified event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'verified');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('telah diverifikasi oleh Admin');
});

test('notify sends to school for rejected event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'rejected', ['reason' => 'Dokumen tidak lengkap']);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('DITOLAK');
    expect($whatsapp->message_content)->toContain('Dokumen tidak lengkap');
});

test('notify sends to school for supplier_assigned event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'supplier_assigned');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Anak Perusahaan telah ditunjuk');
});

test('notify sends to school for items_prepared event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'items_prepared');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Barang pengadaan telah disiapkan');
});

test('notify cancelled by school routes to admin pusat', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;
    $schoolUser = User::factory()->school()->create(['school_id' => $pr->school_id]);

    $service->notify($pr, 'cancelled', ['reason' => 'Anggaran dialihkan'], $schoolUser);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('08111222333');
    expect($whatsapp->message_content)->toContain('DIBATALKAN');
    expect($whatsapp->message_content)->toContain('Anggaran dialihkan');
});

test('notify cancelled by admin routes to school', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;
    $admin = User::factory()->admin()->create();

    $service->notify($pr, 'cancelled', ['reason' => 'Kesalahan data'], $admin);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('DIBATALKAN');
});

test('notify sends to school for receipt_verified event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'receipt_verified');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Penerimaan barang telah diverifikasi');
});

test('notify sends to school for completed event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'completed');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Pengadaan telah SELESAI');
});

test('notify does not create notification for unknown event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'unknown_event');

    expect(Notification::withoutEvents(fn () => Notification::count()))->toBe(0);
});

test('notify does not create whatsapp notification when admin phone is empty', function () {
    config(['waha.admin_cv_phone' => '']);
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'submitted');

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());
    expect($whatsapp)->toBeNull();
});

test('notify does not create email notification when admin email is empty', function () {
    config(['waha.admin_cv_email' => '']);
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'submitted');

    $email = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_EMAIL)->first());
    expect($email)->toBeNull();
});

test('createNotification creates notification with correct fields', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $notification = Notification::withoutEvents(fn () => $service->createNotification(
        $pr,
        'submitted',
        Notification::CHANNEL_WHATSAPP,
        '6281234567890',
        'Test message'
    ));

    expect($notification)->toBeInstanceOf(Notification::class);
    expect($notification->procurement_request_id)->toBe($pr->id);
    expect($notification->event_type)->toBe('submitted');
    expect($notification->channel)->toBe(Notification::CHANNEL_WHATSAPP);
    expect($notification->recipient)->toBe('6281234567890');
    expect($notification->message_content)->toBe('Test message');
    expect($notification->status)->toBe(Notification::STATUS_PENDING);
});

test('notify stores event_type on notification', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'verified');

    $notification = Notification::withoutEvents(fn () => Notification::first());

    expect($notification->event_type)->toBe('verified');
});

test('notify stores triggered_by when user provided', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'submitted', [], $user);

    $notification = Notification::withoutEvents(fn () => Notification::first());

    expect($notification->triggered_by)->toBe($user->id);
});

test('notify stores null triggered_by when no user provided', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'submitted');

    $notification = Notification::withoutEvents(fn () => Notification::first());

    expect($notification->triggered_by)->toBeNull();
});

test('createNotification returns null for duplicate within 5 minutes', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    // Create first notification
    $first = Notification::withoutEvents(fn () => $service->createNotification(
        $pr,
        'submitted',
        Notification::CHANNEL_WHATSAPP,
        '6281234567890',
        'Test message'
    ));

    expect($first)->toBeInstanceOf(Notification::class);

    // Try to create duplicate
    $duplicate = Notification::withoutEvents(fn () => $service->createNotification(
        $pr,
        'submitted',
        Notification::CHANNEL_WHATSAPP,
        '6281234567890',
        'Test message'
    ));

    expect($duplicate)->toBeNull();
    expect(Notification::count())->toBe(1);
});

test('createNotification allows duplicate after 5 minutes', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    // Create old notification (10 minutes ago)
    Notification::withoutEvents(fn () => Notification::factory()->create([
        'procurement_request_id' => $pr->id,
        'event_type' => 'submitted',
        'channel' => Notification::CHANNEL_WHATSAPP,
        'created_at' => now()->subMinutes(10),
    ]));

    // Should allow new notification since old one is > 5 minutes
    $new = Notification::withoutEvents(fn () => $service->createNotification(
        $pr,
        'submitted',
        Notification::CHANNEL_WHATSAPP,
        '6281234567890',
        'Test message'
    ));

    expect($new)->toBeInstanceOf(Notification::class);
});

test('createNotification allows different channel for same event', function () {
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    // Create WhatsApp notification
    Notification::withoutEvents(fn () => $service->createNotification(
        $pr,
        'submitted',
        Notification::CHANNEL_WHATSAPP,
        '6281234567890',
        'Test message'
    ));

    // Should allow email for same event
    $email = Notification::withoutEvents(fn () => $service->createNotification(
        $pr,
        'submitted',
        Notification::CHANNEL_EMAIL,
        'test@test.com',
        'Test message'
    ));

    expect($email)->toBeInstanceOf(Notification::class);
    expect(Notification::count())->toBe(2);
});

test('notify sends to school for needs_revision event with reason', function () {
    config(['waha.admin_cv_phone' => '08111222333']);
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'needs_revision', ['reason' => 'Dokumen tidak lengkap']);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('REVISI');
    expect($whatsapp->message_content)->toContain('Dokumen tidak lengkap');
});

test('notify sends to school for record_payment event', function () {
    config(['waha.admin_cv_phone' => '08111222333']);
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'record_payment', [
        'amount' => 1500000,
        'method' => 'Transfer Bank',
        'reference' => 'INV-2024-001',
        'paidAt' => '2024-01-15',
    ]);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Pembayaran pengadaan telah dicatat');
    expect($whatsapp->message_content)->toContain('Rp 1.500.000');
});

test('notify sends to school for confirm_payment event', function () {
    config(['waha.admin_cv_phone' => '08111222333']);
    $pr = createServicePrWithSchool();
    $service = new NotificationService;

    $service->notify($pr, 'confirm_payment', [
        'amount' => 1500000,
        'receiptNumber' => 'KW/123/2024',
    ]);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());

    expect($whatsapp)->not->toBeNull();
    expect($whatsapp->recipient)->toBe('081234567890');
    expect($whatsapp->message_content)->toContain('Pembayaran pengadaan telah dikonfirmasi');
    expect($whatsapp->message_content)->toContain('KW/123/2024');
});

test('notify does not create whatsapp notification when school phone is empty', function () {
    config(['waha.admin_cv_phone' => '08111222333']);
    $school = School::factory()->create(['phone_number' => '', 'email' => 'sekolah@test.com']);
    $pr = ProcurementRequest::factory()->create(['school_id' => $school->id]);
    $service = new NotificationService;

    $service->notify($pr, 'needs_revision', ['reason' => 'Alasan revisi']);

    $whatsapp = Notification::withoutEvents(fn () => Notification::byChannel(Notification::CHANNEL_WHATSAPP)->first());
    expect($whatsapp)->toBeNull();
});
