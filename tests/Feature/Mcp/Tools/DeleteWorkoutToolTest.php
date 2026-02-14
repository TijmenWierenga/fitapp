<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\DeleteWorkoutTool;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseMissing;

it('deletes upcoming workout successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(DeleteWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Workout deleted successfully');

    assertDatabaseMissing('workouts', [
        'id' => $workout->id,
    ]);
});

it('deletes today\'s workout successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->setHour(8),
    ]);

    $response = WorkoutServer::actingAs($user)->tool(DeleteWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk();

    assertDatabaseMissing('workouts', [
        'id' => $workout->id,
    ]);
});

it('deletes completed workout successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(DeleteWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Workout deleted successfully');

    assertDatabaseMissing('workouts', [
        'id' => $workout->id,
    ]);
});

it('deletes past workout successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDays(2),
    ]);

    $response = WorkoutServer::actingAs($user)->tool(DeleteWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertOk()
        ->assertSee('Workout deleted successfully');

    assertDatabaseMissing('workouts', [
        'id' => $workout->id,
    ]);
});

it('fails to delete workout owned by different user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $workout = Workout::factory()->for($user1)->create();

    $response = WorkoutServer::actingAs($user2)->tool(DeleteWorkoutTool::class, [
        'workout_id' => $workout->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied.');
});

it('fails with non-existent workout_id', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(DeleteWorkoutTool::class, [
        'workout_id' => 99999,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied.');
});
