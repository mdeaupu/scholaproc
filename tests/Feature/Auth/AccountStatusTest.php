<?php

use App\Models\School;
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

test('school user cannot log in while the school is suspended', function () {
    $school = School::factory()->create(['status' => 'suspended']);
    $user = User::factory()->create([
        'role' => 'school',
        'school_id' => $school->id,
        'status' => 'active',
    ]);

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertHasErrors('form.username');

    $this->assertGuest();
});

test('school user session is terminated when the school is suspended', function () {
    $school = School::factory()->create(['status' => 'active']);
    $user = User::factory()->create([
        'role' => 'school',
        'school_id' => $school->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $school->update(['status' => 'suspended']);

    $response = $this->get(route('dashboard.school'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $this->assertGuest();
});

test('school user cannot log in when the school is soft-deleted', function () {
    $school = School::factory()->create();
    $user = User::factory()->create([
        'role' => 'school',
        'school_id' => $school->id,
        'status' => 'active',
    ]);

    $school->delete();

    $component = Volt::test('pages.auth.login')
        ->set('form.username', $user->username)
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertHasErrors('form.username');

    $this->assertGuest();
});
