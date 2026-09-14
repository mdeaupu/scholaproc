<?php

use App\Livewire\Notification\NotificationInbox;
use App\Models\Notification;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

function createSchoolUser(): User
{
    $school = School::factory()->create();

    return User::factory()->create([
        'role' => 'school',
        'school_id' => $school->id,
    ]);
}

function createSchoolNotification(User $school, string $event = 'verified', array $overrides = []): Notification
{
    $pr = ProcurementRequest::factory()->create([
        'school_id' => $school->school_id,
    ]);

    return Notification::withoutEvents(fn () => Notification::factory()->create(array_merge([
        'procurement_request_id' => $pr->id,
        'event_type' => $event,
    ], $overrides)));
}

test('school user can access notification inbox page', function () {
    $school = createSchoolUser();
    actingAs($school);

    Livewire::test(NotificationInbox::class)
        ->assertStatus(200)
        ->assertViewHas('notifications');
});

test('admin cannot access notification inbox page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    actingAs($admin);

    Livewire::test(NotificationInbox::class)
        ->assertStatus(403);
});

test('superadmin cannot access notification inbox page', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    actingAs($superadmin);

    Livewire::test(NotificationInbox::class)
        ->assertStatus(403);
});

test('notification inbox only shows school-related notifications', function () {
    $school = createSchoolUser();
    actingAs($school);

    $schoolNotification = createSchoolNotification($school, 'verified');
    $otherSchoolNotification = createSchoolNotification($school, 'rejected');

    $adminNotification = Notification::withoutEvents(fn () => Notification::factory()->create([
        'event_type' => 'submitted',
    ]));

    Livewire::test(NotificationInbox::class)
        ->assertViewHas('notifications', function ($notifications) use ($schoolNotification, $otherSchoolNotification, $adminNotification) {
            $ids = $notifications->pluck('id')->toArray();

            return in_array($schoolNotification->id, $ids)
                && in_array($otherSchoolNotification->id, $ids)
                && !in_array($adminNotification->id, $ids);
        });
});

test('notification inbox only shows school-allowed event types', function () {
    $school = createSchoolUser();
    actingAs($school);

    $verified = createSchoolNotification($school, 'verified');
    $rejected = createSchoolNotification($school, 'rejected');
    $completed = createSchoolNotification($school, 'completed');
    $submitted = createSchoolNotification($school, 'submitted');

    Livewire::test(NotificationInbox::class)
        ->assertViewHas('notifications', function ($notifications) use ($verified, $rejected, $completed, $submitted) {
            $ids = $notifications->pluck('id')->toArray();

            return in_array($verified->id, $ids)
                && in_array($rejected->id, $ids)
                && in_array($completed->id, $ids)
                && !in_array($submitted->id, $ids);
        });
});

test('notification inbox shows unread count', function () {
    $school = createSchoolUser();
    actingAs($school);

    createSchoolNotification($school, 'verified');
    createSchoolNotification($school, 'rejected');
    createSchoolNotification($school, 'completed', ['read_at' => now()]);

    Livewire::test(NotificationInbox::class)
        ->assertViewHas('unreadCount', 2);
});

test('toggleRead marks unread notification as read', function () {
    $school = createSchoolUser();
    actingAs($school);

    $notification = createSchoolNotification($school, 'verified');

    Livewire::test(NotificationInbox::class)
        ->call('toggleRead', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('toggleRead marks read notification as unread', function () {
    $school = createSchoolUser();
    actingAs($school);

    $notification = createSchoolNotification($school, 'verified', ['read_at' => now()]);

    Livewire::test(NotificationInbox::class)
        ->call('toggleRead', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('showDetail opens modal and marks notification as read', function () {
    $school = createSchoolUser();
    actingAs($school);

    $notification = createSchoolNotification($school, 'verified');

    Livewire::test(NotificationInbox::class)
        ->call('showDetail', $notification->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('detailNotification.id', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('school user cannot toggle read on other school notification', function () {
    $school = createSchoolUser();
    $otherSchool = createSchoolUser();

    actingAs($school);

    $otherNotification = createSchoolNotification($otherSchool, 'verified');

    Livewire::test(NotificationInbox::class)
        ->call('toggleRead', $otherNotification->id)
        ->assertStatus(403);
});

test('school user cannot view detail of other school notification', function () {
    $school = createSchoolUser();
    $otherSchool = createSchoolUser();

    actingAs($school);

    $otherNotification = createSchoolNotification($otherSchool, 'verified');

    Livewire::test(NotificationInbox::class)
        ->call('showDetail', $otherNotification->id)
        ->assertStatus(403);
});

test('notification inbox can filter by channel', function () {
    $school = createSchoolUser();
    actingAs($school);

    $whatsapp = createSchoolNotification($school, 'verified', ['channel' => 'whatsapp']);
    $email = createSchoolNotification($school, 'verified', ['channel' => 'email']);

    Livewire::test(NotificationInbox::class)
        ->set('channelFilter', 'whatsapp')
        ->assertViewHas('notifications', function ($notifications) use ($whatsapp, $email) {
            $ids = $notifications->pluck('id')->toArray();

            return in_array($whatsapp->id, $ids) && !in_array($email->id, $ids);
        });
});

test('notification inbox can search by message content', function () {
    $school = createSchoolUser();
    actingAs($school);

    $notification = createSchoolNotification($school, 'verified', [
        'message_content' => 'Pengadaan ABC telah diverifikasi',
    ]);

    Livewire::test(NotificationInbox::class)
        ->set('search', 'ABC')
        ->assertViewHas('notifications', function ($notifications) use ($notification) {
            return $notifications->pluck('id')->contains($notification->id);
        });
});
