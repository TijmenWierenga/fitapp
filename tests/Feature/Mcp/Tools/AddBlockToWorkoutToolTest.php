<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\AddBlockToWorkoutTool;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;

use function Pest\Laravel\assertDatabaseHas;

it('adds a group block to a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'group',
        'label' => 'Warm-up',
    ]);

    $response->assertOk()
        ->assertSee('Block added successfully')
        ->assertSee('Warm-up');

    assertDatabaseHas('workout_blocks', [
        'workout_id' => $workout->id,
        'type' => 'group',
        'label' => 'Warm-up',
        'position' => 0,
    ]);
});

it('adds an interval block to a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'interval',
        'label' => 'Easy run',
        'duration_seconds' => 600,
        'intensity' => 'easy',
    ]);

    $response->assertOk()
        ->assertSee('Block added successfully');

    assertDatabaseHas('interval_blocks', [
        'duration_seconds' => 600,
        'intensity' => 'easy',
    ]);
});

it('adds an exercise group block', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'exercise_group',
        'group_type' => 'superset',
        'rounds' => 3,
        'rest_between_rounds_seconds' => 90,
    ]);

    $response->assertOk()
        ->assertSee('Block added successfully');

    assertDatabaseHas('exercise_groups', [
        'group_type' => 'superset',
        'rounds' => 3,
        'rest_between_rounds_seconds' => 90,
    ]);
});

it('adds a rest block', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'rest',
        'duration_seconds' => 60,
    ]);

    $response->assertOk();

    assertDatabaseHas('rest_blocks', [
        'duration_seconds' => 60,
    ]);
});

it('adds a note block', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'note',
        'content' => 'Focus on form',
    ]);

    $response->assertOk();

    assertDatabaseHas('note_blocks', [
        'content' => 'Focus on form',
    ]);
});

it('nests a block inside a group', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $group = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'parent_id' => $group->id,
        'type' => 'rest',
        'duration_seconds' => 30,
    ]);

    $response->assertOk();

    assertDatabaseHas('workout_blocks', [
        'workout_id' => $workout->id,
        'parent_id' => $group->id,
        'type' => 'rest',
    ]);
});

it('auto-increments position', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'group',
        'label' => 'First',
    ]);

    WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'group',
        'label' => 'Second',
    ]);

    expect($workout->allBlocks()->count())->toBe(2);

    $positions = $workout->allBlocks()->orderBy('position')->pluck('position')->all();
    expect($positions)->toBe([0, 1]);
});

it('fails to add block to completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'group',
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot modify a completed workout');
});

it('fails to add block to another user workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'group',
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});

it('fails with invalid block type', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'invalid',
    ]);

    $response->assertHasErrors();
});

it('sets repeat count on a block', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'type' => 'group',
        'label' => 'Repeat section',
        'repeat_count' => 3,
        'rest_between_repeats_seconds' => 60,
    ]);

    $response->assertOk();

    assertDatabaseHas('workout_blocks', [
        'workout_id' => $workout->id,
        'repeat_count' => 3,
        'rest_between_repeats_seconds' => 60,
    ]);
});

it('fails to nest in non-group parent', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $rest = \App\Models\RestBlock::create(['duration_seconds' => 30]);
    $block = WorkoutBlock::factory()->rest()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'rest_block',
        'blockable_id' => $rest->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(AddBlockToWorkoutTool::class, [
        'workout_id' => $workout->id,
        'parent_id' => $block->id,
        'type' => 'note',
        'content' => 'Hello',
    ]);

    $response->assertHasErrors()
        ->assertSee('Parent block not found or is not a group block');
});
