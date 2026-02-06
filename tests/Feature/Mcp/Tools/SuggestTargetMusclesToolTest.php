<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\SuggestTargetMusclesTool;
use App\Models\User;

it('returns suggested target muscles', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(SuggestTargetMusclesTool::class, []);

    $response->assertOk()
        ->assertSee('suggestions')
        ->assertSee('muscle_group')
        ->assertSee('fatigue_score')
        ->assertSee('ready_for_heavy');
});

it('returns muscles sorted by fatigue score ascending', function () {
    $user = User::factory()->create();
    $workout = \App\Models\Workout::factory()->for($user)->completed()->create();

    \App\Models\WorkoutMuscleLoadSnapshot::create([
        'workout_id' => $workout->id,
        'muscle_group' => 'chest',
        'total_load' => 100,
        'source_breakdown' => [['description' => 'Bench Press', 'load' => 100]],
        'completed_at' => now()->subHours(1),
    ]);

    $response = WorkoutServer::actingAs($user)->tool(SuggestTargetMusclesTool::class, []);

    $response->assertOk()
        ->assertSee('suggestions')
        ->assertSee('chest');
});

it('includes muscle labels', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(SuggestTargetMusclesTool::class, []);

    $response->assertOk()
        ->assertSee('muscle_label');
});
