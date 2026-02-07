<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\ReorderBlocksTool;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;

it('reorders blocks', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $block1 = WorkoutBlock::factory()->group('First')->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);
    $block2 = WorkoutBlock::factory()->group('Second')->create([
        'workout_id' => $workout->id,
        'position' => 1,
    ]);
    $block3 = WorkoutBlock::factory()->group('Third')->create([
        'workout_id' => $workout->id,
        'position' => 2,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(ReorderBlocksTool::class, [
        'workout_id' => $workout->id,
        'block_ids' => [$block3->id, $block1->id, $block2->id],
    ]);

    $response->assertOk()
        ->assertSee('Blocks reordered successfully');

    expect($block3->fresh()->position)->toBe(0);
    expect($block1->fresh()->position)->toBe(1);
    expect($block2->fresh()->position)->toBe(2);
});

it('fails when block ids do not belong to workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();
    $otherWorkout = Workout::factory()->for($user)->upcoming()->create();

    $block1 = WorkoutBlock::factory()->group()->create(['workout_id' => $workout->id]);
    $block2 = WorkoutBlock::factory()->group()->create(['workout_id' => $otherWorkout->id]);

    $response = WorkoutServer::actingAs($user)->tool(ReorderBlocksTool::class, [
        'workout_id' => $workout->id,
        'block_ids' => [$block1->id, $block2->id],
    ]);

    $response->assertHasErrors()
        ->assertSee('One or more block IDs do not belong');
});

it('fails when blocks have different parents', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $parent = WorkoutBlock::factory()->group()->create(['workout_id' => $workout->id, 'position' => 0]);
    $rootBlock = WorkoutBlock::factory()->group()->create(['workout_id' => $workout->id, 'position' => 1]);
    $childBlock = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'parent_id' => $parent->id,
        'position' => 0,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(ReorderBlocksTool::class, [
        'workout_id' => $workout->id,
        'block_ids' => [$rootBlock->id, $childBlock->id],
    ]);

    $response->assertHasErrors()
        ->assertSee('All blocks must share the same parent');
});

it('fails on completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $block = WorkoutBlock::factory()->group()->create(['workout_id' => $workout->id]);

    $response = WorkoutServer::actingAs($user)->tool(ReorderBlocksTool::class, [
        'workout_id' => $workout->id,
        'block_ids' => [$block->id],
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot modify a completed workout');
});
