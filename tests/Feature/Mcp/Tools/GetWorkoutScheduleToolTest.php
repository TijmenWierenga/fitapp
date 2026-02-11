<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetWorkoutScheduleTool;
use App\Models\User;
use App\Models\Workout;

it('returns upcoming and completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'scheduled_at' => now()->addDay(),
    ]);
    Workout::factory()->for($user)->completed()->create([
        'name' => 'Evening Yoga',
        'rpe' => 5,
        'feeling' => 4,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutScheduleTool::class);

    $response->assertOk()
        ->assertSee('Morning Run')
        ->assertSee('Evening Yoga')
        ->assertSee('5/10')
        ->assertSee('4/5');
});

it('returns empty schedule message when no workouts exist', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutScheduleTool::class);

    $response->assertOk()
        ->assertSee('No upcoming workouts scheduled')
        ->assertSee('No completed workouts yet');
});

it('respects custom limits', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->count(5)->create([
        'scheduled_at' => now()->addDay(),
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkoutScheduleTool::class, [
        'upcoming_limit' => 2,
    ]);

    $response->assertOk();
});
