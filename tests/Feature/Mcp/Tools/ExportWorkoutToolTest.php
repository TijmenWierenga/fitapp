<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\ExportWorkoutTool;
use App\Models\User;
use App\Models\Workout;

it('exports a workout as base64 FIT data', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'scheduled_at' => '2026-03-15 07:00:00',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(ExportWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"success": true')
        ->assertSee('"filename": "2026-03-15-morning-run.fit"')
        ->assertSee('content_type')
        ->assertSee('"data":');
});

it('returns error for non-existent workout', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(ExportWorkoutTool::class, [
        'workout_id' => 99999,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found');
});

it('returns error when workout belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $response = WorkoutServer::actingAs($user)->tool(ExportWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found');
});
