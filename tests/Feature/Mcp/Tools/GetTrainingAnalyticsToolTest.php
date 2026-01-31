<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetTrainingAnalyticsTool;
use App\Models\User;
use App\Models\Workout;

it('returns analytics for completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->completed()->count(3)->create([
        'completed_at' => now()->subDays(1),
        'rpe' => 6,
        'feeling' => 4,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, [
        'weeks' => 4,
    ]);

    $response->assertOk()
        ->assertSee('"total_completed":3')
        ->assertSee('"average_rpe":6')
        ->assertSee('"average_feeling":4')
        ->assertSee('"period_weeks":4');
});

it('defaults to 4 weeks when no weeks parameter provided', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, []);

    $response->assertOk()
        ->assertSee('"period_weeks":4');
});

it('calculates completion rate correctly', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->completed()->count(3)->create([
        'completed_at' => now()->subDay(),
    ]);
    Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDays(2),
        'completed_at' => null,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, []);

    $response->assertOk()
        ->assertSee('"completion_rate":75');
});

it('returns zero completion rate when no workouts exist', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, []);

    $response->assertOk()
        ->assertSee('"total_completed":0')
        ->assertSee('"completion_rate":0');
});

it('calculates current streak', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'completed_at' => now(),
        'rpe' => 5,
        'feeling' => 3,
    ]);
    Workout::factory()->for($user)->create([
        'completed_at' => now()->subDay(),
        'rpe' => 5,
        'feeling' => 3,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, []);

    $response->assertOk()
        ->assertSee('"current_streak_days":2');
});

it('returns activity distribution', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->completed()->count(2)->create([
        'activity' => 'run',
        'completed_at' => now()->subDay(),
    ]);
    Workout::factory()->for($user)->completed()->create([
        'activity' => 'strength',
        'completed_at' => now()->subDay(),
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, []);

    $response->assertOk()
        ->assertSee('"run":2')
        ->assertSee('"strength":1');
});

it('fails when weeks exceeds max', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetTrainingAnalyticsTool::class, [
        'weeks' => 13,
    ]);

    $response->assertHasErrors();
});
