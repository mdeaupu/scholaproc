<?php

use App\Models\User;
use App\Livewire\Forms\LoginForm;
use App\Livewire\Actions\Logout;
use Livewire\Livewire;
use Livewire\Component;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

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

test('user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'username' => 'budi_admin',
        'password' => 'password',
    ]);

    Livewire::test(TestLoginComponent::class)
        ->set('form.username', 'budi_admin')
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->id)->toBe($user->id);
});

test('user cannot log in with invalid credentials', function () {
    User::factory()->create([
        'username' => 'budi_admin',
        'password' => 'password',
    ]);

    Livewire::test(TestLoginComponent::class)
        ->set('form.username', 'budi_admin')
        ->set('form.password', 'wrong_password')
        ->call('login')
        ->assertHasErrors(['form.username']);

    expect(Auth::check())->toBeFalse();
});

test('login is rate limited after 5 failed attempts', function () {
    User::factory()->create([
        'username' => 'targeted_user',
        'password' => 'password',
    ]);

    $component = Livewire::test(TestLoginComponent::class)
        ->set('form.username', 'targeted_user')
        ->set('form.password', 'wrong_pass');

    for ($i = 0; $i < 5; $i++) {
        $component->call('login');
    }

    $component->call('login')
        ->assertHasErrors(['form.username']);
});

test('authenticated user can log out', function () {
    $user = User::factory()->create();
    Auth::login($user);

    expect(Auth::check())->toBeTrue();

    (new Logout())();

    expect(Auth::check())->toBeFalse();
});

test('owner can reset password of another user via explicit route', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $targetUser = User::factory()->create(['role' => 'admin_school', 'username' => 'school_admin']);

    Auth::login($owner);

    $response = $this->put(route('auth.reset-password', $targetUser->id), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    expect(Auth::attempt(['username' => 'school_admin', 'password' => 'newpassword123']))->toBeTrue();
});

test('non-owner cannot reset password of another user due to route gate middleware', function () {
    $adminSchool = User::factory()->create(['role' => 'admin_school']);
    $targetUser = User::factory()->create(['role' => 'admin_cv']);

    Auth::login($adminSchool);

    $response = $this->put(route('auth.reset-password', $targetUser->id), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(403);
});

test('reset password requires valid input validation via explicit route', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $targetUser = User::factory()->create(['role' => 'admin_school']);

    Auth::login($owner);

    $response = $this->put(route('auth.reset-password', $targetUser->id), [
        'password' => 'short',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('owner is redirected to owner dashboard', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    Auth::login($owner);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('dashboard.owner'));
});

test('admin cv is redirected to cv dashboard', function () {
    $adminCv = User::factory()->create(['role' => 'admin_cv']);

    Auth::login($adminCv);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('dashboard.cv'));
});

test('admin school is redirected to school dashboard', function () {
    $adminSchool = User::factory()->create(['role' => 'admin_school']);

    Auth::login($adminSchool);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('dashboard.school'));
});

test('unauthenticated user cannot access dashboard and is redirected to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});