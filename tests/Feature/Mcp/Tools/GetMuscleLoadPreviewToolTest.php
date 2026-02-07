<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetMuscleLoadPreviewTool;
use App\Models\ActivityMuscleLoad;
use App\Models\IntervalBlock;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;

it('returns muscle load preview for a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create(['activity' => 'run']);

    ActivityMuscleLoad::create([
        'activity' => 'run',
        'muscle_group' => 'quadriceps',
        'role' => 'primary',
        'load_factor' => 0.8,
    ]);

    $interval = IntervalBlock::create([
        'duration_seconds' => 1200,
        'intensity' => 'moderate',
    ]);

    WorkoutBlock::factory()->interval()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'interval_block',
        'blockable_id' => $interval->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetMuscleLoadPreviewTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('muscle_loads')
        ->assertSee('quadriceps')
        ->assertSee('total_load');
});

it('returns empty muscle loads for workout without blocks', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetMuscleLoadPreviewTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"total_load":0');
});

it('fails for another user workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetMuscleLoadPreviewTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});
