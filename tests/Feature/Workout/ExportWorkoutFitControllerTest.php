<?php

use App\Models\User;
use App\Models\Workout;

it('downloads a FIT file', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'scheduled_at' => '2026-03-15 07:00:00',
    ]);

    $response = $this->actingAs($user)
        ->get(route('workouts.export-fit', $workout));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/octet-stream')
        ->assertHeader('Content-Disposition', 'attachment; filename="2026-03-15-morning-run.fit"');

    $content = $response->getContent();
    expect(substr($content, 8, 4))->toBe('.FIT');
});

it('requires authentication', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = $this->get(route('workouts.export-fit', $workout));

    $response->assertRedirect(route('login'));
});

it('denies access to workouts of other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('workouts.export-fit', $workout));

    $response->assertForbidden();
});

it('uses scheduled date in filename', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Leg Day',
        'scheduled_at' => '2026-06-01 10:00:00',
    ]);

    $response = $this->actingAs($user)
        ->get(route('workouts.export-fit', $workout));

    $response->assertHeader('Content-Disposition', 'attachment; filename="2026-06-01-leg-day.fit"');
});
