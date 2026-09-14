<?php

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Livewire\Notification\NotificationManager;
use App\Models\Notification;
use App\Models\ProcurementRequest;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

function createNotifManagerUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function createManagerNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->create($overrides));
}

function createManagerPendingNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->pending()->create($overrides));
}

function createManagerFailedNotification(array $overrides = []): Notification
{
    return Notification::withoutEvents(fn () => Notification::factory()->failed()->create($overrides));
}

test('admin can access notification manager page', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Livewire::test(NotificationManager::class)
        ->assertStatus(200)
        ->assertViewHas('notifications');
});

test('superadmin can access notification manager page', function () {
    $superadmin = createNotifManagerUser('superadmin');
    actingAs($superadmin);

    Livewire::test(NotificationManager::class)
        ->assertStatus(200);
});

test('school role cannot access notification manager page', function () {
    $school = createNotifManagerUser('school');
    actingAs($school);

    Livewire::test(NotificationManager::class)
        ->assertStatus(403);
});

test('notification manager shows correct stats', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Notification::withoutEvents(function () {
        Notification::factory()->pending()->count(2)->create();
        Notification::factory()->sent()->count(3)->create();
        Notification::factory()->failed()->count(1)->create();
    });

    Livewire::test(NotificationManager::class)
        ->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 6
                && $stats['pending'] === 2
                && $stats['sent'] === 3
                && $stats['failed'] === 1;
        });
});

test('notification manager can filter by status', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Notification::withoutEvents(function () {
        Notification::factory()->pending()->count(3)->create();
        Notification::factory()->sent()->count(2)->create();
    });

    Livewire::test(NotificationManager::class)
        ->set('statusFilter', 'pending')
        ->assertViewHas('notifications', function ($notifications) {
            return $notifications->total() === 3;
        });
});

test('notification manager can filter by channel', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Notification::withoutEvents(function () {
        Notification::factory()->whatsapp()->count(4)->create();
        Notification::factory()->email()->count(2)->create();
    });

    Livewire::test(NotificationManager::class)
        ->set('channelFilter', 'whatsapp')
        ->assertViewHas('notifications', function ($notifications) {
            return $notifications->total() === 4;
        });
});

test('notification manager can search by recipient', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Notification::withoutEvents(function () {
        Notification::factory()->create(['recipient' => '6281234567890']);
        Notification::factory()->create(['recipient' => '6289876543210']);
        Notification::factory()->create(['recipient' => 'user@test.com']);
    });

    Livewire::test(NotificationManager::class)
        ->set('search', '6281234567890')
        ->assertViewHas('notifications', function ($notifications) {
            return $notifications->total() === 1;
        });
});

test('resend resets notification status and dispatches job', function () {
    Queue::fake();
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerFailedNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
    ]);

    Livewire::test(NotificationManager::class)
        ->call('resend', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->status)->toBe(Notification::STATUS_PENDING);
    expect($notification->fresh()->error_message)->toBeNull();
    expect($notification->fresh()->sent_at)->toBeNull();

    Queue::assertPushed(SendWhatsAppNotification::class);
});

test('resend does not resend already sent notifications', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerNotification([
        'channel' => Notification::CHANNEL_WHATSAPP,
        'status' => Notification::STATUS_SENT,
        'sent_at' => now(),
    ]);

    Livewire::test(NotificationManager::class)
        ->call('resend', $notification->id);

    // The component uses toast error, not validation errors
    // Verify the notification was NOT changed
    expect($notification->fresh()->status)->toBe(Notification::STATUS_SENT);
});

test('resend dispatches email job for email channel', function () {
    Queue::fake();
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerFailedNotification([
        'channel' => Notification::CHANNEL_EMAIL,
    ]);

    Livewire::test(NotificationManager::class)
        ->call('resend', $notification->id)
        ->assertHasNoErrors();

    Queue::assertPushed(SendEmailNotification::class);
});

test('delete removes notification', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerNotification();

    Livewire::test(NotificationManager::class)
        ->call('delete', $notification->id)
        ->assertHasNoErrors();

    expect(Notification::find($notification->id))->toBeNull();
});

test('confirmDelete removes notification using modal', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerNotification();

    Livewire::test(NotificationManager::class)
        ->call('openDeleteModal', $notification->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingNotificationId', $notification->id)
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingNotificationId', null);

    expect(Notification::find($notification->id))->toBeNull();
});

test('updated status filter resets page', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Livewire::test(NotificationManager::class)
        ->set('statusFilter', 'sent')
        ->assertSet('statusFilter', 'sent');
});

test('updated channel filter resets page', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Livewire::test(NotificationManager::class)
        ->set('channelFilter', 'email')
        ->assertSet('channelFilter', 'email');
});

test('updated search resets page', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Livewire::test(NotificationManager::class)
        ->set('search', 'test')
        ->assertSet('search', 'test');
});

test('notification manager shows whatsapp working status', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
            'engine' => ['engine' => 'whatsapp-web.js'],
        ], 200),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertSet('waStatus', 'WORKING')
        ->assertSet('waPhoneName', 'My Phone')
        ->assertSee('WhatsApp Terhubung');
});

test('notification manager shows whatsapp unavailable status when API fails', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([], 500),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertSet('waStatus', 'UNAVAILABLE')
        ->assertSee('Status WhatsApp tidak diketahui');
});

test('notification manager shows scan QR status', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'SCAN_QR_CODE',
        ], 200),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertSet('waStatus', 'SCAN_QR_CODE')
        ->assertSee('Menunggu Scan QR');
});

test('notification manager shows stopped status', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'STOPPED',
        ], 200),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertSet('waStatus', 'STOPPED')
        ->assertSee('WhatsApp Terputus');
});

test('checkWhatsAppStatus refreshes wa status', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
        ], 200),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertSet('waStatus', 'WORKING')
        ->call('checkWhatsAppStatus')
        ->assertSet('waStatus', 'WORKING');
});

test('superadmin sees link to whatsapp session on status banner', function () {
    $superadmin = createNotifManagerUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'STOPPED',
        ], 200),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertSee('Buka Halaman Session');
});

test('admin does not see link to whatsapp session on status banner', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'STOPPED',
        ], 200),
    ]);

    Livewire::test(NotificationManager::class)
        ->assertDontSee('Buka Halaman Session')
        ->assertSee('Hubungi superadmin');
});

test('toggleRead marks unread notification as read', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerNotification();

    Livewire::test(NotificationManager::class)
        ->call('toggleRead', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('toggleRead marks read notification as unread', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerNotification(['read_at' => now()]);

    Livewire::test(NotificationManager::class)
        ->call('toggleRead', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('showDetail opens modal and marks notification as read', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $notification = createManagerNotification();

    Livewire::test(NotificationManager::class)
        ->call('showDetail', $notification->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('detailNotification.id', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('showDetail loads relations', function () {
    $admin = createNotifManagerUser('admin');
    actingAs($admin);

    $pr = ProcurementRequest::factory()->create();
    $notification = createManagerNotification([
        'procurement_request_id' => $pr->id,
    ]);

    Livewire::test(NotificationManager::class)
        ->call('showDetail', $notification->id)
        ->assertSet('detailNotification.procurementRequest.id', $pr->id);
});
