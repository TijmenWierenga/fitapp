<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\CompleteWorkoutTool;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseHas;

it('completes workout successfully', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $response->assertOk()
        ->assertSee('Workout completed successfully')
        ->assertSee('Hard');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $workout->refresh();
    expect($workout->isCompleted())->toBeTrue();
});

it('includes rpe label in response', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 2,
        'feeling' => 5,
    ]);

    $response->assertOk()
        ->assertSee('Very Easy');
});

it('fails to complete already completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('already completed');
});

it('fails with RPE out of range', function (int $invalidRpe) {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => $invalidRpe,
        'feeling' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('RPE must be between');
})->with([0, 11, -1]);

it('fails with feeling out of range', function (int $invalidFeeling) {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => $invalidFeeling,
    ]);

    $response->assertHasErrors()
        ->assertSee('Feeling must be between');
})->with([0, 6, -1]);

it('fails to complete workout owned by different user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $workout = Workout::factory()->for($user1)->create();

    $response = WorkoutServer::actingAs($user2)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});
