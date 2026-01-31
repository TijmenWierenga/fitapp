<?php

use App\Enums\Workout\Activity;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\UpdateWorkoutTool;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseHas;

it('updates workout name successfully', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Old Name']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertSee('New Name')
        ->assertSee('Workout updated successfully');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'name' => 'New Name',
    ]);
});

it('updates workout activity successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['activity' => Activity::Run]);

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'activity' => 'strength',
    ]);

    $response->assertOk();

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'activity' => 'strength',
    ]);
});

it('updates workout scheduled_at successfully', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'scheduled_at' => '2026-02-01 08:00:00',
    ]);

    $response->assertOk();

    $workout->refresh();
    expect($workout->scheduled_at->timezone->getName())->toBe('UTC');
});

it('updates workout notes successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['notes' => 'Old notes']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'notes' => 'Updated notes',
    ]);

    $response->assertOk();

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'notes' => 'Updated notes',
    ]);
});

it('fails to update workout owned by different user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $workout = Workout::factory()->for($user1)->create();

    $response = WorkoutServer::actingAs($user2)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'name' => 'Hacked Name',
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});

it('fails to update completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'name' => 'New Name',
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot update completed workouts');
});

it('fails with non-existent workout_id', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => 99999,
        'name' => 'Test',
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});
