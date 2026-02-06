<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\RemoveBlockTool;
use App\Models\NoteBlock;
use App\Models\RestBlock;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;

it('removes a block from a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $rest = RestBlock::create(['duration_seconds' => 60]);
    $block = WorkoutBlock::factory()->rest()->create([
        'workout_id' => $workout->id,
        'blockable_type' => 'rest_block',
        'blockable_id' => $rest->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveBlockTool::class, [
        'workout_id' => $workout->id,
        'block_id' => $block->id,
    ]);

    $response->assertOk()
        ->assertSee('Block removed successfully');

    expect(WorkoutBlock::find($block->id))->toBeNull();
    expect(RestBlock::find($rest->id))->toBeNull();
});

it('removes children recursively', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $parent = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $note = NoteBlock::create(['content' => 'Child note']);
    $child = WorkoutBlock::factory()->note()->create([
        'workout_id' => $workout->id,
        'parent_id' => $parent->id,
        'blockable_type' => 'note_block',
        'blockable_id' => $note->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveBlockTool::class, [
        'workout_id' => $workout->id,
        'block_id' => $parent->id,
    ]);

    $response->assertOk();

    expect(WorkoutBlock::find($parent->id))->toBeNull();
    expect(WorkoutBlock::find($child->id))->toBeNull();
    expect(NoteBlock::find($note->id))->toBeNull();
});

it('fails on completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $block = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveBlockTool::class, [
        'workout_id' => $workout->id,
        'block_id' => $block->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot modify a completed workout');
});

it('fails when block does not belong to workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $otherWorkout = Workout::factory()->for($user)->upcoming()->create();
    $block = WorkoutBlock::factory()->group()->create([
        'workout_id' => $otherWorkout->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveBlockTool::class, [
        'workout_id' => $workout->id,
        'block_id' => $block->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Block not found');
});

it('fails for another user workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->upcoming()->create();
    $block = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveBlockTool::class, [
        'workout_id' => $workout->id,
        'block_id' => $block->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});
