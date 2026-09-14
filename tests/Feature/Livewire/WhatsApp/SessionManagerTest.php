<?php

use App\Livewire\WhatsApp\SessionManager;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

function createSessionUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

beforeEach(function () {
    config(['waha.base_url' => 'http://localhost:3000']);
    config(['waha.session' => 'default']);
    config(['waha.api_key' => 'test-api-key']);
});

test('superadmin can access session manager page', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
            'engine' => ['engine' => 'whatsapp-web.js'],
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->assertStatus(200);
});

test('admin cannot access session manager page', function () {
    $admin = createSessionUser('admin');
    actingAs($admin);

    Livewire::test(SessionManager::class)
        ->assertStatus(403);
});

test('school role cannot access session manager page', function () {
    $school = createSessionUser('school');
    actingAs($school);

    Livewire::test(SessionManager::class)
        ->assertStatus(403);
});

test('session manager shows working status', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
            'engine' => ['engine' => 'whatsapp-web.js'],
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->assertSet('status', 'WORKING')
        ->assertSet('phoneName', 'My Phone')
        ->assertSet('engineInfo', 'whatsapp-web.js');
});

test('session manager shows unavailable when API fails', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([], 500),
    ]);

    Livewire::test(SessionManager::class)
        ->assertSet('status', 'UNAVAILABLE');
});

test('startSession calls WAHA API and refreshes status', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default/start' => Http::response([
            'status' => 'STARTING',
        ], 200),
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'SCAN_QR_CODE',
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->call('startSession')
        ->assertHasNoErrors();
});

test('stopSession calls WAHA API and refreshes status', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default/stop' => Http::response([
            'status' => 'STOPPED',
        ], 200),
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'STOPPED',
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->call('stopSession')
        ->assertHasNoErrors();
});

test('refreshStatus updates status from API', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'Updated Phone'],
            'engine' => ['engine' => 'wwebjs'],
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->call('refreshStatus')
        ->assertSet('status', 'WORKING')
        ->assertSet('phoneName', 'Updated Phone')
        ->assertSet('engineInfo', 'wwebjs');
});

test('pollStatus does nothing when status unchanged', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
            'engine' => ['engine' => 'whatsapp-web.js'],
        ], 200),
    ]);

    $component = Livewire::test(SessionManager::class)
        ->assertSet('status', 'WORKING');

    // Poll with same status
    $component->call('pollStatus')
        ->assertSet('status', 'WORKING');
});

test('loadQR fetches QR code from API', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    $qrData = 'data:image/png;base64,fake-qr';

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'SCAN_QR_CODE',
        ], 200),
        'http://localhost:3000/api/default/auth/qr?format=image' => Http::response($qrData, 200),
    ]);

    Livewire::test(SessionManager::class)
        ->call('loadQR')
        ->assertSet('qrCode', $qrData);
});

test('session manager returns correct view', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->assertSee('WhatsApp');
});

test('openChangeNumberModal sets modal state', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->assertSet('showChangeNumberModal', false)
        ->call('openChangeNumberModal')
        ->assertSet('showChangeNumberModal', true);
});

test('closeChangeNumberModal clears modal state', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->call('openChangeNumberModal')
        ->assertSet('showChangeNumberModal', true)
        ->call('closeChangeNumberModal')
        ->assertSet('showChangeNumberModal', false);
});

test('logoutSession calls logout API and starts new session', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
        ], 200),
        'http://localhost:3000/api/sessions/default/logout' => Http::response([
            'status' => 'LOGGED_OUT',
        ], 200),
        'http://localhost:3000/api/sessions/default/start' => Http::response([
            'status' => 'STARTING',
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->call('openChangeNumberModal')
        ->call('logoutSession')
        ->assertSet('showChangeNumberModal', false)
        ->assertHasNoErrors();
});

test('logoutSession shows error when logout fails', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
        ], 200),
        'http://localhost:3000/api/sessions/default/logout' => Http::response([], 500),
    ]);

    Livewire::test(SessionManager::class)
        ->call('openChangeNumberModal')
        ->call('logoutSession')
        ->assertSet('showChangeNumberModal', true);
});

test('working status shows change number button', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'WORKING',
            'me' => ['pushName' => 'My Phone'],
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->assertSee('Ganti Nomor');
});

test('non-working status does not show change number trigger button', function () {
    $superadmin = createSessionUser('superadmin');
    actingAs($superadmin);

    Http::fake([
        'http://localhost:3000/api/sessions/default' => Http::response([
            'status' => 'STOPPED',
        ], 200),
    ]);

    Livewire::test(SessionManager::class)
        ->assertDontSee('wire:click="openChangeNumberModal"');
});
