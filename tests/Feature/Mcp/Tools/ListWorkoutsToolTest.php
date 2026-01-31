<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\ListWorkoutsTool;
use App\Models\User;
use App\Models\Workout;

it('lists all workouts by default', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, []);

    $response->assertOk()
        ->assertSee('"count":3')
        ->assertSee('"filter":"all"');
});

it('lists upcoming workouts only', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->upcoming()->count(2)->create();
    Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, [
        'filter' => 'upcoming',
    ]);

    $response->assertOk()
        ->assertSee('"count":2')
        ->assertSee('"filter":"upcoming"');
});

it('lists completed workouts only', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->upcoming()->create();
    Workout::factory()->for($user)->completed()->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, [
        'filter' => 'completed',
    ]);

    $response->assertOk()
        ->assertSee('"count":3')
        ->assertSee('"filter":"completed"');
});

it('lists overdue workouts only', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDays(2),
        'completed_at' => null,
    ]);
    Workout::factory()->for($user)->upcoming()->create();
    Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, [
        'filter' => 'overdue',
    ]);

    $response->assertOk()
        ->assertSee('"count":1')
        ->assertSee('"filter":"overdue"');
});

it('respects limit parameter', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->count(10)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, [
        'limit' => 5,
    ]);

    $response->assertOk()
        ->assertSee('"count":5');
});

it('applies default limit of 20', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->count(25)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, []);

    $response->assertOk()
        ->assertSee('"count":20');
});

it('converts dates to user timezone in response', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    Workout::factory()->for($user)->create([
        'scheduled_at' => '2026-01-26 06:00:00',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, []);

    $response->assertOk()
        ->assertSee('2026-01-26T07:00:00+01:00');
});

it('returns empty list for user with no workouts', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, []);

    $response->assertOk()
        ->assertSee('"count":0')
        ->assertSee('"workouts":[]');
});

it('fails with invalid filter', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, [
        'filter' => 'invalid_filter',
    ]);

    $response->assertHasErrors();
});

it('fails with limit exceeding max', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListWorkoutsTool::class, [
        'limit' => 101,
    ]);

    $response->assertHasErrors();
});
