<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetWorkoutTool;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;

it('fetches a single workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"success": true')
        ->assertSee('Morning Run')
        ->assertSee('"completed": false');
});

it('includes rpe and feeling for completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->completed()->create([
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"completed": true')
        ->assertSee('"rpe": 7')
        ->assertSee('"feeling": 4')
        ->assertSee('"rpe_label": "Hard"');
});

it('returns error for non-existent workout', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutTool::class, [
        'workout_id' => 99999,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found');
});

it('returns error when workout belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found');
});

it('converts dates to user timezone', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => '2026-01-26 06:00:00',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('2026-01-26T07:00:00+01:00');
});

it('returns full nested structure with sections blocks and exercises', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Structured Workout']);

    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);

    $strength = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Bench Press',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Structured Workout')
        ->assertSee('Main')
        ->assertSee('straight_sets')
        ->assertSee('Bench Press')
        ->assertSee('strength_exercise')
        ->assertSee('"target_sets": 3')
        ->assertSee('"target_reps_max": 10');
});
