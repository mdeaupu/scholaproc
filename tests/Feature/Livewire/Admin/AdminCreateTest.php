<?php

use App\Livewire\Admin\AdminCreate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('superadmin dapat membuka halaman tambah admin baru', function () {
    $owner = User::factory()->superAdmin()->create();

    actingAs($owner);

    $this->get(route('admins.create'))
        ->assertOk()
        ->assertSee('Tambah Admin Baru')
        ->assertSee('Informasi Akun')
        ->assertSee('Informasi Hak Akses')
        ->assertSee('Hak Akses Utama')
        ->assertDontSee('Riwayat Aktivitas')
        ->assertDontSee('Nonaktifkan Akun');
});

test('admin dan akun sekolah tidak dapat membuka halaman tambah admin', function () {
    actingAs(User::factory()->admin()->create());
    Livewire::test(AdminCreate::class)->assertStatus(403);

    actingAs(User::factory()->school()->create());
    Livewire::test(AdminCreate::class)->assertStatus(403);
});

test('superadmin dapat menyimpan admin baru beserta aktivitas', function () {
    Storage::fake('public');

    $owner = User::factory()->superAdmin()->create();

    actingAs($owner);

    Livewire::test(AdminCreate::class)
        ->set('name', 'Admin Baru')
        ->set('email', 'admin.baru@cvmsa.co.id')
        ->set('username', 'admin_baru')
        ->set('password', 'rahasia123')
        ->set('notes', 'Catatan admin baru')
        ->set('photo', UploadedFile::fake()->image('avatar.jpg'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admins.index'));

    $admin = User::where('username', 'admin_baru')->firstOrFail();

    expect($admin->name)->toBe('Admin Baru')
        ->and($admin->role)->toBe('admin')
        ->and($admin->status)->toBe('active')
        ->and($admin->notes)->toBe('Catatan admin baru')
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->hasRole('superadmin'))->toBeFalse()
        ->and($admin->avatar_path)->not->toBeNull();

    Storage::disk('public')->assertExists($admin->avatar_path);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'admin',
        'description' => 'admin-created',
    ]);
});

test('form tambah admin memvalidasi field wajib dan keunikan', function () {
    $owner = User::factory()->superAdmin()->create();

    actingAs($owner);

    $existing = User::factory()->admin()->create([
        'email' => 'dipakai@cvmsa.co.id',
        'username' => 'dipakai',
    ]);

    Livewire::test(AdminCreate::class)
        ->set('name', '')
        ->set('email', $existing->email)
        ->set('username', $existing->username)
        ->set('password', '123')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'email' => 'unique',
            'username' => 'unique',
            'password' => 'min',
        ]);
});

test('form tambah admin tidak menyediakan pilihan role', function () {
    $owner = User::factory()->superAdmin()->create();

    actingAs($owner);

    Livewire::test(AdminCreate::class)
        ->assertSee('Pengajuan')
        ->assertDontSee('Pengelolaan Master Data')
        ->assertDontSeeHtml('wire:model.live="role"');
});
