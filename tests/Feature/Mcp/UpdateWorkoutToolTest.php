<?php

use App\Mcp\Servers\WorkoutPlannerServer;
use App\Mcp\Tools\UpdateWorkoutTool;
use App\Models\User;
use App\Models\Workout;

test('updates workout notes', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'notes' => 'Original notes',
    ]);

    $response = WorkoutPlannerServer::actingAs($user)
        ->tool(UpdateWorkoutTool::class, [
            'workout_id' => $workout->id,
            'notes' => 'Updated with detailed pace targets: 9:00-9:30/mile',
        ]);

    $response->assertOk();
    expect($workout->refresh()->notes)->toContain('pace targets');
});

test('cannot update another users workout', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $workout = Workout::factory()->create(['user_id' => $user1->id]);

    $response = WorkoutPlannerServer::actingAs($user2)
        ->tool(UpdateWorkoutTool::class, [
            'workout_id' => $workout->id,
            'name' => 'Hacked',
        ]);

    $response->assertHasErrors();
    expect($workout->refresh()->name)->not->toBe('Hacked');
});

test('cannot update completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->completed()->create(['user_id' => $user->id]);

    $response = WorkoutPlannerServer::actingAs($user)
        ->tool(UpdateWorkoutTool::class, [
            'workout_id' => $workout->id,
            'name' => 'New Name',
        ]);

    $response->assertHasErrors();
});
