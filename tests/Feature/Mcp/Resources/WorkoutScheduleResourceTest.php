<?php

use App\Mcp\Resources\WorkoutScheduleResource;
use App\Mcp\Servers\WorkoutServer;
use App\Models\User;
use App\Models\Workout;

it('returns upcoming and completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->count(3)->create();
    Workout::factory()->for($user)->completed()->count(2)->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutScheduleResource::class, []);

    $response->assertOk()
        ->assertSee('Upcoming Workouts')
        ->assertSee('Recently Completed Workouts');
});

it('shows empty message when no upcoming workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutScheduleResource::class, []);

    $response->assertOk()
        ->assertSee('No upcoming workouts scheduled');
});

it('shows empty message when no completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutScheduleResource::class, []);

    $response->assertOk()
        ->assertSee('No completed workouts yet');
});

it('includes workout details in output', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->create([
        'name' => 'Morning Run',
    ]);
    Workout::factory()->for($user)->completed()->create([
        'name' => 'Evening Strength',
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $response = WorkoutServer::actingAs($user)->resource(WorkoutScheduleResource::class, []);

    $response->assertOk()
        ->assertSee('Morning Run')
        ->assertSee('Evening Strength')
        ->assertSee('RPE: 7/10');
});
