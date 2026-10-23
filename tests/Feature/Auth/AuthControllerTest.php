<?php

use App\Livewire\Actions\Logout;
use App\Livewire\Forms\LoginForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class TestLoginComponent extends Component
{
    public LoginForm $form;

    public function login()
    {
        $this->form->authenticate();
    }

    public function render()
    {
        return '<div></div>';
    }
}

test('user can log in with valid credentials via livewire form', function () {
    $user = User::factory()->school()->create([
        'username' => 'admin_sekolah_1',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(TestLoginComponent::class)
        ->set('form.username', 'admin_sekolah_1')
        ->set('form.password', 'password123')
        ->call('login')
        ->assertHasNoErrors();

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->id)->toBe($user->id);
});

test('user cannot log in with invalid credentials', function () {
    User::factory()->create([
        'username' => 'admin_sekolah_1',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(TestLoginComponent::class)
        ->set('form.username', 'admin_sekolah_1')
        ->set('form.password', 'wrong_password')
        ->call('login')
        ->assertHasErrors(['form.username']);

    expect(Auth::check())->toBeFalse();
});

test('authenticated user can log out', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    expect(Auth::check())->toBeTrue();

    $logoutAction = new Logout;
    $logoutAction();

    $this->assertGuest();
});

test('superadmin can reset password of another user', function () {
    $admin = User::factory()->superAdmin()->create();

    $targetUser = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $response = $this->put(route('auth.reset-password', $targetUser->id), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');
});

test('superadmin cannot reset password of another superadmin', function () {
    $admin = User::factory()->superAdmin()->create();

    $targetSuperadmin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    $response = $this->put(route('auth.reset-password', $targetSuperadmin->id), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(403);
});

test('non-superadmin cannot reset password of another user', function () {
    $userSchool = User::factory()->school()->create();
    $targetUser = User::factory()->create(['role' => 'admin']);

    Auth::login($userSchool);

    $response = $this->put(route('auth.reset-password', $targetUser->id), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(403);
});
