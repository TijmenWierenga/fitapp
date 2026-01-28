<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetWorkoutTool;
use App\Models\User;
use App\Models\Workout;

it('fetches a single workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
    ]);

    $response = WorkoutServer::tool(GetWorkoutTool::class, [
        'user_id' => $user->id,
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"success":true')
        ->assertSee('Morning Run')
        ->assertSee('"completed":false');
});

it('includes rpe and feeling for completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->completed()->create([
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $response = WorkoutServer::tool(GetWorkoutTool::class, [
        'user_id' => $user->id,
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"completed":true')
        ->assertSee('"rpe":7')
        ->assertSee('"feeling":4')
        ->assertSee('"rpe_label":"Hard"');
});

it('returns error for non-existent workout', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::tool(GetWorkoutTool::class, [
        'user_id' => $user->id,
        'workout_id' => 99999,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found');
});

it('returns error when workout belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $response = WorkoutServer::tool(GetWorkoutTool::class, [
        'user_id' => $user->id,
        'workout_id' => $workout->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found');
});

it('fails with invalid user_id', function () {
    $response = WorkoutServer::tool(GetWorkoutTool::class, [
        'user_id' => 99999,
        'workout_id' => 1,
    ]);

    $response->assertHasErrors()
        ->assertSee('User not found');
});

it('converts dates to user timezone', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => '2026-01-26 06:00:00',
    ]);

    $response = WorkoutServer::tool(GetWorkoutTool::class, [
        'user_id' => $user->id,
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('2026-01-26T07:00:00+01:00');
});
