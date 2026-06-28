<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('createAdminCv berhasil membuat admin CV dengan data valid', function () {
    $data = [
        'username' => 'admin_test',
        'name' => 'Admin Test',
        'email' => 'test@example.com',
        'password' => 'secret123',
    ];

    $user = User::createAdminCv($data);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->username)->toBe('admin_test');
    expect($user->name)->toBe('Admin Test');
    expect($user->email)->toBe('test@example.com');
    expect($user->role)->toBe('admin_cv');
    expect($user->status)->toBe('active');
    expect($user->school_id)->toBeNull();
    expect(Hash::check('secret123', $user->password))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'username' => 'admin_test',
        'name' => 'Admin Test',
        'email' => 'test@example.com',
        'role' => 'admin_cv',
        'status' => 'active',
        'school_id' => null,
    ]);
});

test('updateAdminCv hanya mengubah username, name, email jika password tidak diisi', function () {
    $user = User::factory()->create(['role' => 'admin_cv']);
    $oldPassword = $user->password;

    $data = [
        'username' => 'new_username',
        'name' => 'New Name',
        'email' => 'new@example.com',
    ];

    $user->updateAdminCv($data);
    $user->refresh();

    expect($user->username)->toBe('new_username');
    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe('new@example.com');
    expect($user->password)->toBe($oldPassword);
});

test('updateAdminCv mengganti password jika field password diisi', function () {
    $user = User::factory()->create(['role' => 'admin_cv', 'password' => bcrypt('oldpass')]);

    $data = [
        'username' => 'new_username',
        'name' => 'New Name',
        'password' => 'newpass123',
    ];

    $user->updateAdminCv($data);
    $user->refresh();

    expect(Hash::check('newpass123', $user->password))->toBeTrue();
    expect($user->username)->toBe('new_username');
    expect($user->name)->toBe('New Name');
});

test('activate dan deactivate dapat mengubah status akun', function () {
    $user = User::factory()->create(['role' => 'admin_cv', 'status' => 'active']);

    $user->deactivate();
    expect($user->status)->toBe('suspended');
    expect($user->isActive())->toBeFalse();

    $user->activate();
    expect($user->status)->toBe('active');
    expect($user->isActive())->toBeTrue();
});

test('resetPassword mereset password ke default Password123!', function () {
    $user = User::factory()->create(['role' => 'admin_cv', 'password' => bcrypt('old')]);

    $user->resetPassword();
    expect(Hash::check('Password123!', $user->fresh()->password))->toBeTrue();
});

test('resetPassword dapat menerima password kustom', function () {
    $user = User::factory()->create(['role' => 'admin_cv', 'password' => bcrypt('old')]);

    $user->resetPassword('MyNewPass123');
    expect(Hash::check('MyNewPass123', $user->fresh()->password))->toBeTrue();
});

test('changePassword mengganti password dengan password baru', function () {
    $user = User::factory()->create(['role' => 'admin_cv', 'password' => bcrypt('old')]);

    $user->changePassword('NewPass456');
    expect(Hash::check('NewPass456', $user->fresh()->password))->toBeTrue();
});

test('scope adminCv hanya mengembalikan user dengan role admin_cv', function () {
    User::factory()->create(['role' => 'owner']);
    User::factory()->create(['role' => 'admin_school']);
    $adminCv = User::factory()->create(['role' => 'admin_cv']);

    $result = User::adminCv()->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($adminCv->id);
});

test('scope active hanya mengembalikan user dengan status active', function () {
    User::factory()->create(['role' => 'admin_cv', 'status' => 'suspended']);
    $activeUser = User::factory()->create(['role' => 'admin_cv', 'status' => 'active']);

    $result = User::active()->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($activeUser->id);
});

test('scope inactive hanya mengembalikan user dengan status suspended', function () {
    User::factory()->create(['role' => 'admin_cv', 'status' => 'active']);
    $suspendedUser = User::factory()->create(['role' => 'admin_cv', 'status' => 'suspended']);

    $result = User::inactive()->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($suspendedUser->id);
});

test('method pengecekan role berfungsi dengan benar', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $adminCv = User::factory()->create(['role' => 'admin_cv']);
    $adminSchool = User::factory()->create(['role' => 'admin_school']);

    expect($owner->isOwner())->toBeTrue();
    expect($owner->isAdminCv())->toBeFalse();
    expect($owner->isAdminSchool())->toBeFalse();

    expect($adminCv->isAdminCv())->toBeTrue();
    expect($adminCv->isOwner())->toBeFalse();
    expect($adminCv->isAdminSchool())->toBeFalse();

    expect($adminSchool->isAdminSchool())->toBeTrue();
    expect($adminSchool->isOwner())->toBeFalse();
    expect($adminSchool->isAdminCv())->toBeFalse();
});

test('method otorisasi (canManage, canProcess) berfungsi dengan benar', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $adminCv = User::factory()->create(['role' => 'admin_cv']);

    expect($owner->canManageSchools())->toBeTrue();
    expect($owner->canManageSuppliers())->toBeTrue();
    expect($owner->canProcessProcurement())->toBeFalse();

    expect($adminCv->canManageSchools())->toBeFalse();
    expect($adminCv->canManageSuppliers())->toBeFalse();
    expect($adminCv->canProcessProcurement())->toBeTrue();
});