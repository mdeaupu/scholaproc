<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->school()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.school', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->school()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('login redirects superadmin to the superadmin dashboard', function () {
    $user = User::factory()->superAdmin()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertRedirect(route('dashboard.superadmin', absolute: false));
});

test('navigation menu can be rendered', function () {
    $user = User::factory()->school()->create();

    $this->actingAs($user);

    $response = $this->followingRedirects()->get('/dashboard');

    $response
        ->assertOk()
        ->assertSeeVolt('layout.navigation');
});

test('admin sidebar does not expose subsidiary management', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.sidebar-menu');

    $component->assertDontSee('Manajemen Anak Perusahaan');
});

test('superadmin sidebar links to subsidiary management', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.sidebar-menu');

    $component
        ->assertSee(route('superadmin.subsidiaries.index'), escape: false)
        ->assertSee('Manajemen Anak Perusahaan');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.sidebar-menu');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
