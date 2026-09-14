<?php

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('cleanup notifications removes old notifications', function () {
    // Create old notification (100 days ago)
    Notification::withoutEvents(fn () => Notification::factory()->create([
        'created_at' => now()->subDays(100),
    ]));

    // Create recent notification (10 days ago)
    Notification::withoutEvents(fn () => Notification::factory()->create([
        'created_at' => now()->subDays(10),
    ]));

    expect(Notification::count())->toBe(2);

    $this->artisan('notifications:cleanup', ['--days' => 90])
        ->expectsOutputToContain('Berhasil menghapus 1 notifikasi')
        ->assertExitCode(0);

    expect(Notification::count())->toBe(1);
});

test('cleanup notifications keeps notifications within retention period', function () {
    // Create notifications within retention period
    Notification::withoutEvents(fn () => Notification::factory()->count(3)->create([
        'created_at' => now()->subDays(30),
    ]));

    $this->artisan('notifications:cleanup', ['--days' => 90])
        ->expectsOutputToContain('Berhasil menghapus 0 notifikasi')
        ->assertExitCode(0);

    expect(Notification::count())->toBe(3);
});

test('cleanup notifications handles empty table', function () {
    $this->artisan('notifications:cleanup', ['--days' => 90])
        ->expectsOutputToContain('Berhasil menghapus 0 notifikasi')
        ->assertExitCode(0);
});

test('cleanup notifications defaults to 90 days', function () {
    // Create notification at 91 days old
    Notification::withoutEvents(fn () => Notification::factory()->create([
        'created_at' => now()->subDays(91),
    ]));

    // Create notification at 89 days old
    Notification::withoutEvents(fn () => Notification::factory()->create([
        'created_at' => now()->subDays(89),
    ]));

    $this->artisan('notifications:cleanup')
        ->expectsOutputToContain('Berhasil menghapus 1 notifikasi')
        ->assertExitCode(0);

    expect(Notification::count())->toBe(1);
});
