<?php

use App\Livewire\Admin\AdminIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

function createUser(string $role, array $attributes = []): User
{
    return User::factory()->create(array_merge($attributes, ['role' => $role]));
}

test('owner dapat mengakses halaman manajemen admin CV', function () {
    $owner = createUser('owner');
    actingAs($owner);

    Livewire::test(AdminIndex::class)
        ->assertStatus(200)
        ->assertViewHas('admins');
});

test('admin_cv tidak dapat mengakses halaman dan mendapat 403', function () {
    $adminCv = createUser('admin_cv');
    actingAs($adminCv);

    Livewire::test(AdminIndex::class)
        ->assertStatus(403);
});

test('guest tidak dapat mengakses halaman', function () {
    Livewire::test(AdminIndex::class)
        ->assertStatus(403);
});

test('halaman indeks menampilkan daftar admin CV dengan paginasi', function () {
    $owner = createUser('owner');
    actingAs($owner);

    User::factory()->count(5)->create(['role' => 'admin_cv']);

    Livewire::test(AdminIndex::class)
        ->assertViewHas('admins', fn($admins) => $admins->total() >= 5);
});

test('pencarian berdasarkan nama atau username berfungsi', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $specific = User::factory()->create([
        'role' => 'admin_cv',
        'name' => 'Joko Widodo',
        'username' => 'jokowi_cv'
    ]);

    Livewire::test(AdminIndex::class)
        ->set('search', 'Joko')
        ->assertViewHas('admins', fn($admins) => $admins->contains($specific));

    Livewire::test(AdminIndex::class)
        ->set('search', 'jokowi')
        ->assertViewHas('admins', fn($admins) => $admins->contains($specific));
});

test('tombol create membuka modal form dengan properti kosong', function () {
    $owner = createUser('owner');
    actingAs($owner);

    Livewire::test(AdminIndex::class)
        ->call('create')
        ->assertSet('isFormModalOpen', true)
        ->assertSet('isEditMode', false)
        ->assertSet('username', '')
        ->assertSet('name', '')
        ->assertSet('email', '')
        ->assertSet('password', '');
});

test('store berhasil menambahkan admin CV baru', function () {
    $owner = createUser('owner');
    actingAs($owner);

    Livewire::test(AdminIndex::class)
        ->set('username', 'admin_baru')
        ->set('name', 'Admin Baru')
        ->set('email', 'baru@example.com')
        ->set('password', 'rahasia123')
        ->call('store')
        ->assertHasNoErrors()
        ->assertStatus(200)
        ->assertSet('isFormModalOpen', false);

    $this->assertDatabaseHas('users', [
        'username' => 'admin_baru',
        'name' => 'Admin Baru',
        'email' => 'baru@example.com',
        'role' => 'admin_cv',
    ]);
});

test('validasi store: username wajib dan unik', function () {
    $owner = createUser('owner');
    actingAs($owner);

    User::factory()->create(['username' => 'sama', 'role' => 'admin_cv']);

    Livewire::test(AdminIndex::class)
        ->set('username', 'sama')
        ->set('name', 'Test')
        ->set('password', '123456')
        ->call('store')
        ->assertHasErrors(['username' => 'unique']);
});

test('validasi store: password minimal 6 karakter', function () {
    $owner = createUser('owner');
    actingAs($owner);

    Livewire::test(AdminIndex::class)
        ->set('username', 'newuser')
        ->set('name', 'Test')
        ->set('password', '123')
        ->call('store')
        ->assertHasErrors(['password' => 'min']);
});

test('show menampilkan detail admin di modal', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv']);

    Livewire::test(AdminIndex::class)
        ->call('show', $admin->id)
        ->assertSet('selectedUser.id', $admin->id)
        ->assertSet('isDetailModalOpen', true);
});

test('edit mengisi form dengan data admin yang dipilih', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create([
        'role' => 'admin_cv',
        'name' => 'Lama',
        'username' => 'lama_cv',
        'email' => 'lama@example.com'
    ]);

    Livewire::test(AdminIndex::class)
        ->call('edit', $admin->id)
        ->assertSet('userId', $admin->id)
        ->assertSet('username', 'lama_cv')
        ->assertSet('name', 'Lama')
        ->assertSet('email', 'lama@example.com')
        ->assertSet('password', '')
        ->assertSet('isEditMode', true)
        ->assertSet('isFormModalOpen', true);
});

test('update berhasil mengubah data admin tanpa password', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv']);

    Livewire::test(AdminIndex::class)
        ->set('userId', $admin->id)
        ->set('username', 'baru_cv')
        ->set('name', 'Nama Baru')
        ->set('email', 'baru@example.com')
        ->set('password', '')
        ->call('update')
        ->assertHasNoErrors()
        ->assertStatus(200)
        ->assertSet('isFormModalOpen', false);

    $admin->refresh();
    expect($admin->username)->toBe('baru_cv');
    expect($admin->name)->toBe('Nama Baru');
    expect($admin->email)->toBe('baru@example.com');
});

test('update berhasil mengganti password jika diisi', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv', 'password' => bcrypt('oldpass')]);

    Livewire::test(AdminIndex::class)
        ->set('userId', $admin->id)
        ->set('username', $admin->username)
        ->set('name', $admin->name)
        ->set('password', 'newpass123')
        ->call('update')
        ->assertHasNoErrors()
        ->assertStatus(200);

    $admin->refresh();
    expect(Hash::check('newpass123', $admin->password))->toBeTrue();
});

test('validasi update: username dan email harus unik kecuali dirinya sendiri', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv', 'username' => 'admin1']);
    $other = User::factory()->create(['role' => 'admin_cv', 'username' => 'admin2', 'email' => 'other@example.com']);

    Livewire::test(AdminIndex::class)
        ->set('userId', $admin->id)
        ->set('username', 'admin2')
        ->set('name', 'Test')
        ->set('email', 'other@example.com')
        ->call('update')
        ->assertHasErrors(['username' => 'unique', 'email' => 'unique']);
});

test('suspend membekukan admin yang aktif via modal konfirmasi', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv', 'status' => 'active']);

    Livewire::test(AdminIndex::class)
        ->call('confirmSuspend', $admin->id, $admin->name)
        ->assertSet('targetAdminId', $admin->id)
        ->assertSet('confirmingSuspend', true)
        ->call('suspend')
        ->assertStatus(200)
        ->assertSet('confirmingSuspend', false);

    expect($admin->fresh()->status)->toBe('suspended');
});

test('activate mengaktifkan kembali admin yang dibekukan secara langsung', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv', 'status' => 'suspended']);

    Livewire::test(AdminIndex::class)
        ->call('activate', $admin->id)
        ->assertStatus(200);

    expect($admin->fresh()->status)->toBe('active');
});

test('resetPasswordAction mereset password ke default via modal konfirmasi', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv', 'password' => bcrypt('old')]);

    Livewire::test(AdminIndex::class)
        ->call('confirmReset', $admin->id, $admin->name)
        ->assertSet('targetAdminId', $admin->id)
        ->assertSet('confirmingReset', true)
        ->call('resetPasswordAction')
        ->assertStatus(200)
        ->assertSet('confirmingReset', false);

    expect(Hash::check('Password123!', $admin->fresh()->password))->toBeTrue();
});

test('destroy menghapus admin (soft delete) via modal konfirmasi', function () {
    $owner = createUser('owner');
    actingAs($owner);

    $admin = User::factory()->create(['role' => 'admin_cv']);

    Livewire::test(AdminIndex::class)
        ->call('confirmDestroy', $admin->id, $admin->name)
        ->assertSet('targetAdminId', $admin->id)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertStatus(200)
        ->assertSet('confirmingDestroy', false);

    $this->assertSoftDeleted('users', ['id' => $admin->id]);
});