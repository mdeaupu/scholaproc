<?php

use App\Livewire\Admin\AdminIndex;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createUser(string $role, array $attributes = []): User
{
    return User::factory()->create(array_merge($attributes, ['role' => $role]));
}

test('owner dapat mengakses halaman manajemen admin CV', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    Livewire::test(AdminIndex::class)
        ->assertStatus(200)
        ->assertViewHas('admins');
});

test('admin_cv tidak dapat mengakses halaman dan mendapat 403', function () {
    $adminCv = createUser('admin');
    actingAs($adminCv);

    Livewire::test(AdminIndex::class)
        ->assertStatus(403);
});

test('guest tidak dapat mengakses halaman', function () {
    Livewire::test(AdminIndex::class)
        ->assertStatus(403);
});

test('halaman indeks menampilkan daftar admin CV dengan paginasi', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    User::factory()->count(5)->create(['role' => 'admin']);

    Livewire::test(AdminIndex::class)
        ->assertViewHas('admins', fn($admins) => $admins->total() >= 5);
});

test('pencarian berdasarkan nama atau username berfungsi', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $specific = User::factory()->create([
        'role' => 'admin',
        'name' => 'Joko Widodo',
        'username' => 'jokowi_cv',
    ]);

    Livewire::test(AdminIndex::class)
        ->set('search', 'Joko')
        ->assertViewHas('admins', fn($admins) => $admins->contains($specific));

    Livewire::test(AdminIndex::class)
        ->set('search', 'jokowi')
        ->assertViewHas('admins', fn($admins) => $admins->contains($specific));
});

test('show menampilkan detail admin di modal', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::test(AdminIndex::class)
        ->call('show', $admin->id)
        ->assertSet('selectedUser.id', $admin->id)
        ->assertSet('isDetailModalOpen', true)
        ->assertSet('detailTab', 'umum')
        ->assertSee('Detail Admin')
        ->assertSee('Informasi Umum')
        ->assertSee('Hak Akses')
        ->assertSee('Riwayat Aktivitas')
        ->assertSee('Data Pribadi')
        ->assertSee('Terakhir Login')
        ->assertDontSee('Tutup');
});

test('aksi admin tidak dapat menyasar akun sekolah walau id dimanipulasi', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $schoolUser = User::factory()->school()->create();

    $this->expectException(ModelNotFoundException::class);

    Livewire::test(AdminIndex::class)
        ->call('show', $schoolUser->id);
});

test('tabel hanya menampilkan akun admin', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $otherSuperadmin = createUser('superadmin');
    $admin = createUser('admin');

    Livewire::test(AdminIndex::class)
        ->assertViewHas('admins', fn($admins) => $admins->contains($admin)
            && !$admins->contains($owner)
            && !$admins->contains($otherSuperadmin));
});

test('destroy menghapus admin (soft delete) via modal konfirmasi', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::test(AdminIndex::class)
        ->call('confirmDestroy', $admin->id)
        ->assertSet('targetAdminId', $admin->id)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertStatus(200)
        ->assertSet('confirmingDestroy', false);

    $this->assertSoftDeleted('users', ['id' => $admin->id]);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'admin', 'description' => 'admin-deleted']);
});

test('admin dengan activity log sebagai causer dapat dihapus jika tidak memiliki transaksi', function () {
    $owner = createUser('superadmin');
    $admin = createUser('admin');
    actingAs($owner);

    activity('admin')
        ->causedBy($admin)
        ->performedOn($owner)
        ->log('admin-updated');

    Livewire::test(AdminIndex::class)
        ->assertSeeHtml('confirmDestroy(' . $admin->id)
        ->call('confirmDestroy', $admin->id)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertStatus(200);

    $this->assertSoftDeleted('users', ['id' => $admin->id]);
});

test('admin baru dengan activity log sebagai subject dapat dihapus', function () {
    $owner = createUser('superadmin');
    $admin = createUser('admin');
    actingAs($owner);

    activity('admin')
        ->causedBy($owner)
        ->performedOn($admin)
        ->log('admin-created');

    Livewire::test(AdminIndex::class)
        ->assertSeeHtml('confirmDestroy(' . $admin->id)
        ->call('confirmDestroy', $admin->id)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertStatus(200);

    $this->assertSoftDeleted('users', ['id' => $admin->id]);
});

test('destroy menolak penghapusan dan menyembunyikan tombol jika admin punya riwayat', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin']);
    $request = ProcurementRequest::factory()->create();

    ProcurementRequestHistory::create([
        'procurement_request_id' => $request->id,
        'user_id' => $admin->id,
        'status' => 'submitted',
    ]);

    Livewire::test(AdminIndex::class)
        ->assertDontSeeHtml('confirmDestroy(' . $admin->id)
        ->call('confirmDestroy', $admin->id)
        ->assertStatus(403);

    $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
});

test('riwayat verifikasi pengajuan menghalangi penghapusan admin', function () {
    $owner = createUser('superadmin');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin']);
    ProcurementRequest::factory()->create(['verified_by' => $admin->id]);

    Livewire::test(AdminIndex::class)
        ->assertDontSeeHtml('confirmDestroy(' . $admin->id);

    expect($admin->hasTransactionalHistory())->toBeTrue();
});

test('aksi admin tidak dapat menyasar akun superadmin walau id dimanipulasi', function () {
    $owner = createUser('superadmin');
    $otherSuperadmin = createUser('superadmin');
    actingAs($owner);

    $this->expectException(ModelNotFoundException::class);

    Livewire::test(AdminIndex::class)
        ->call('confirmDestroy', $otherSuperadmin->id);
});
