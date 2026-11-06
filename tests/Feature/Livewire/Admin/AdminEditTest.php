<?php

use App\Livewire\Admin\AdminEdit;
use App\Livewire\Admin\AdminIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('superadmin dapat membuka halaman edit data admin', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    actingAs($owner);

    $this->get(route('admins.edit', $admin))
        ->assertOk()
        ->assertSee('Edit Data Admin')
        ->assertSee('Informasi Akun')
        ->assertSee('Informasi Hak Akses')
        ->assertSee('Password Baru')
        ->assertSee('Kosongkan jika tidak ingin mengubah password.')
        ->assertSee('Riwayat Aktivitas')
        ->assertSee('Nonaktifkan Akun')
        ->assertDontSee('Keamanan Akun')
        ->assertDontSee('Reset Kata Sandi');
});

test('admin dan akun sekolah tidak dapat membuka halaman edit', function () {
    $admin = User::factory()->admin()->create();
    $school = User::factory()->school()->create();
    $target = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(AdminEdit::class, ['user' => $target])
        ->assertStatus(403);

    actingAs($school);

    Livewire::test(AdminEdit::class, ['user' => $target])
        ->assertStatus(403);
});

test('halaman edit tidak dapat menyasar akun selain admin', function () {
    $owner = User::factory()->superAdmin()->create();
    $schoolUser = User::factory()->school()->create();
    $otherSuperadmin = User::factory()->superAdmin()->create();

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $schoolUser])
        ->assertStatus(404);

    Livewire::test(AdminEdit::class, ['user' => $otherSuperadmin])
        ->assertStatus(404);
});

test('form terisi dengan data admin yang dipilih', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create([
        'name' => 'Andi Pratama',
        'username' => 'andi.pratama',
        'email' => 'andi@cvmsa.co.id',
        'phone' => '081234567890',
        'notes' => 'Catatan awal',
    ]);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->assertSet('username', 'andi.pratama')
        ->assertSet('name', 'Andi Pratama')
        ->assertSet('email', 'andi@cvmsa.co.id')
        ->assertSet('phone', '081234567890')
        ->assertSet('notes', 'Catatan awal')
        ->assertSet('status', 'active');
});

test('save memperbarui data admin dan mencatat aktivitas', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->set('name', 'Nama Diperbarui')
        ->set('email', 'diperbarui@cvmsa.co.id')
        ->set('phone', '081298765432')
        ->set('notes', 'Catatan baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admins.index'));

    $admin->refresh();

    expect($admin->name)->toBe('Nama Diperbarui')
        ->and($admin->email)->toBe('diperbarui@cvmsa.co.id')
        ->and($admin->phone)->toBe('081298765432')
        ->and($admin->notes)->toBe('Catatan baru')
        ->and($admin->role)->toBe('admin')
        ->and($admin->hasRole('admin'))->toBeTrue();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'admin',
        'description' => 'admin-updated',
    ]);
});

test('role admin tidak dapat diubah melalui halaman edit', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->assertDontSeeHtml('wire:model.live="role"')
        ->assertDontSee('Pengelolaan Master Data');
});

test('save memvalidasi email unik dan nama wajib diisi', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create(['email' => 'dipakai@cvmsa.co.id']);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->set('name', '')
        ->set('email', $other->email)
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'email' => 'unique',
        ]);
});

test('avatar admin dapat diunggah dan disimpan', function () {
    Storage::fake('public');

    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->set('photo', UploadedFile::fake()->image('avatar.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $admin->refresh();

    expect($admin->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($admin->avatar_path);
});

test('nonaktifkan akun melalui modal konfirmasi mencatat aktivitas', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create(['status' => 'active']);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->call('confirmSuspend')
        ->assertSet('confirmingSuspend', true)
        ->call('suspend')
        ->assertSet('confirmingSuspend', false)
        ->assertSet('status', 'suspended');

    expect($admin->fresh()->status)->toBe('suspended');
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'admin',
        'description' => 'admin-suspended',
    ]);
});

test('akun dapat diaktifkan kembali dari halaman edit', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create(['status' => 'suspended']);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->call('activate')
        ->assertSet('status', 'active');

    expect($admin->fresh()->status)->toBe('active');
});

test('policy menolak seluruh aksi terhadap target superadmin', function () {
    $owner = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($owner)->allows('view', $other))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $other))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('changeStatus', $other))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('resetPassword', $other))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $other))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $admin))->toBeTrue();
});

test('mengisi password baru memperbarui password, mengakhiri sesi, dan mencatat aktivitas', function () {
    config(['session.driver' => 'database']);

    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create([
        'password' => bcrypt('old-password'),
        'remember_token' => 'token-lama',
    ]);

    DB::table('sessions')->insert([
        'id' => 'session-'.$admin->id,
        'user_id' => $admin->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->set('password', 'password-baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admins.index'));

    $admin->refresh();

    expect(Hash::check('password-baru', $admin->password))->toBeTrue()
        ->and($admin->remember_token)->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $admin->id)->count())->toBe(0);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'admin',
        'description' => 'admin-password-reset',
    ]);
});

test('mengosongkan password mempertahankan password lama', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create(['password' => bcrypt('old-password')]);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Hash::check('old-password', $admin->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('activity_log', [
        'log_name' => 'admin',
        'description' => 'admin-password-reset',
    ]);
});

test('password baru memvalidasi panjang minimal 8 karakter', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create(['password' => bcrypt('old-password')]);

    actingAs($owner);

    Livewire::test(AdminEdit::class, ['user' => $admin])
        ->set('password', 'pendek')
        ->call('save')
        ->assertHasErrors(['password' => 'min']);

    expect(Hash::check('old-password', $admin->fresh()->password))->toBeTrue();
});

test('dropdown aksi index hanya berisi detail, edit, dan hapus', function () {
    $owner = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    actingAs($owner);

    Livewire::test(AdminIndex::class)
        ->assertSee('Lihat Detail')
        ->assertSee('Edit Data')
        ->assertSee('Hapus')
        ->assertDontSeeHtml('wire:click="confirmSuspend')
        ->assertDontSeeHtml('wire:click="activate(')
        ->assertDontSee('Kelola Hak Akses')
        ->assertDontSee('Reset Password');
});
