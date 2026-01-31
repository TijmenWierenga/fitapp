<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\CreateWorkoutTool;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('creates a workout successfully', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Morning Run',
        'activity' => 'run',
        'scheduled_at' => '2026-01-26 07:00:00',
        'notes' => 'Easy pace',
    ]);

    $response->assertOk()
        ->assertSee('Morning Run')
        ->assertSee('run')
        ->assertSee('Workout created successfully');

    assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'name' => 'Morning Run',
        'activity' => 'run',
        'notes' => 'Easy pace',
    ]);
});

it('creates a workout without notes', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Strength Training',
        'activity' => 'strength',
        'scheduled_at' => '2026-01-27 18:00:00',
    ]);

    $response->assertOk()
        ->assertSee('Strength Training');

    assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'name' => 'Strength Training',
        'notes' => null,
    ]);
});

it('converts user timezone to UTC for storage', function () {
    $user = User::factory()->withTimezone('America/New_York')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Evening HIIT',
        'activity' => 'hiit',
        'scheduled_at' => '2026-01-26 19:00:00',
    ]);

    $response->assertOk();

    $workout = $user->workouts()->first();
    expect($workout->scheduled_at->timezone->getName())->toBe('UTC');
});

it('fails with invalid activity', function (string $invalidActivity) {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => $invalidActivity,
        'scheduled_at' => '2026-01-26 07:00:00',
    ]);

    $response->assertHasErrors()
        ->assertSee('The selected activity is invalid');
})->with(['invalid', 'swimming', 'cycling']);

it('fails with empty activity', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => '',
        'scheduled_at' => '2026-01-26 07:00:00',
    ]);

    $response->assertHasErrors()
        ->assertSee('activity');
});

it('fails with invalid scheduled_at', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => 'run',
        'scheduled_at' => 'not-a-date',
    ]);

    $response->assertHasErrors()
        ->assertSee('valid date');
});

it('trims empty notes to null', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => 'run',
        'scheduled_at' => '2026-01-26 07:00:00',
        'notes' => '   ',
    ]);

    $response->assertOk();

    assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'notes' => null,
    ]);
});
