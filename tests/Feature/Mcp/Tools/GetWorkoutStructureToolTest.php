<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetWorkoutStructureTool;
use App\Models\Exercise;
use App\Models\ExerciseEntry;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\NoteBlock;
use App\Models\RestBlock;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;

it('returns empty block tree for workout without blocks', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutStructureTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"blocks":[]');
});

it('returns nested block structure', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $group = WorkoutBlock::factory()->group('Warm-up')->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $note = NoteBlock::create(['content' => 'Light stretching']);
    WorkoutBlock::factory()->note()->create([
        'workout_id' => $workout->id,
        'parent_id' => $group->id,
        'position' => 0,
        'blockable_type' => 'note_block',
        'blockable_id' => $note->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutStructureTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Warm-up')
        ->assertSee('Light stretching')
        ->assertSee('children');
});

it('includes interval block content', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $interval = IntervalBlock::create([
        'duration_seconds' => 600,
        'intensity' => 'easy',
    ]);

    WorkoutBlock::factory()->interval()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'label' => 'Easy jog',
        'blockable_type' => 'interval_block',
        'blockable_id' => $interval->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutStructureTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Easy jog')
        ->assertSee('"duration_seconds":600')
        ->assertSee('"intensity":"easy"');
});

it('includes exercise group entries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $exerciseGroup = ExerciseGroup::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);
    ExerciseEntry::factory()->create([
        'exercise_group_id' => $exerciseGroup->id,
        'exercise_id' => $exercise->id,
        'sets' => 3,
        'reps' => 10,
    ]);

    WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $exerciseGroup->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutStructureTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Bench Press')
        ->assertSee('entries');
});

it('fails for another user workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutStructureTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});

it('includes rest block content', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $rest = RestBlock::create(['duration_seconds' => 120]);
    WorkoutBlock::factory()->rest()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'rest_block',
        'blockable_id' => $rest->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutStructureTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('"duration_seconds":120');
});
