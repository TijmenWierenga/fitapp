<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetRecoveryStatusTool;
use App\Models\User;
use App\Models\WorkoutMuscleLoadSnapshot;

it('returns recovery status for all muscle groups', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetRecoveryStatusTool::class, []);

    $response->assertOk()
        ->assertSee('recovery_status')
        ->assertSee('fatigue_score')
        ->assertSee('status')
        ->assertSee('ready_for_heavy');
});

it('includes muscle group labels', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetRecoveryStatusTool::class, []);

    $response->assertOk()
        ->assertSee('muscle_label');
});

it('reflects recent workout fatigue', function () {
    $user = User::factory()->create();
    $workout = \App\Models\Workout::factory()->for($user)->completed()->create();

    WorkoutMuscleLoadSnapshot::create([
        'workout_id' => $workout->id,
        'muscle_group' => 'chest',
        'total_load' => 100,
        'source_breakdown' => [['description' => 'Bench Press', 'load' => 100]],
        'completed_at' => now()->subHours(2),
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetRecoveryStatusTool::class, []);

    $response->assertOk()
        ->assertSee('chest')
        ->assertSee('fatigued');
});
