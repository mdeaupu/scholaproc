<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('inactive user cannot log in', function () {
    $user = User::factory()->school()->inactive()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertHasErrors('form.username');

    $this->assertGuest();
});

test('active user can log in', function () {
    $user = User::factory()->admin()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.admin', absolute: false));

    $this->assertAuthenticated();
});

test('inactive user session is terminated on the next request', function () {
    $user = User::factory()->school()->inactive()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard.school'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $this->assertGuest();
});
