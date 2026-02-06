<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\AddExerciseToGroupTool;
use App\Models\Exercise;
use App\Models\ExerciseGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;

use function Pest\Laravel\assertDatabaseHas;

it('adds an exercise to an exercise group', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $group = ExerciseGroup::factory()->create();
    $block = WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $group->id,
    ]);
    $exercise = Exercise::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'sets' => 3,
        'reps' => 10,
        'weight_kg' => 60,
        'rpe_target' => 7,
        'rest_between_sets_seconds' => 90,
    ]);

    $response->assertOk()
        ->assertSee('Exercise added to group successfully')
        ->assertSee($exercise->name);

    assertDatabaseHas('exercise_entries', [
        'exercise_group_id' => $group->id,
        'exercise_id' => $exercise->id,
        'sets' => 3,
        'reps' => 10,
    ]);
});

it('auto-increments position within group', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $group = ExerciseGroup::factory()->create();
    $block = WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $group->id,
    ]);
    $exercise1 = Exercise::factory()->create();
    $exercise2 = Exercise::factory()->create();

    WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => $exercise1->id,
        'sets' => 3,
    ]);

    WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => $exercise2->id,
        'sets' => 3,
    ]);

    $positions = $group->entries()->orderBy('position')->pluck('position')->all();
    expect($positions)->toBe([0, 1]);
});

it('fails when block is not an exercise group', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $block = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
    ]);
    $exercise = Exercise::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'sets' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('Exercise group block not found');
});

it('fails when exercise does not exist', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $group = ExerciseGroup::factory()->create();
    $block = WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $group->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => 99999,
        'sets' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('Exercise not found');
});

it('fails on completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $group = ExerciseGroup::factory()->create();
    $block = WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $group->id,
    ]);
    $exercise = Exercise::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'sets' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot modify a completed workout');
});

it('supports duration-based exercises', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $group = ExerciseGroup::factory()->create();
    $block = WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $group->id,
    ]);
    $exercise = Exercise::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddExerciseToGroupTool::class, [
        'workout_id' => $workout->id,
        'exercise_group_block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'sets' => 3,
        'duration_seconds' => 30,
        'notes' => 'Hold form',
    ]);

    $response->assertOk();

    assertDatabaseHas('exercise_entries', [
        'exercise_group_id' => $group->id,
        'duration_seconds' => 30,
        'notes' => 'Hold form',
    ]);
});
