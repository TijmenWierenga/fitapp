<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetFitnessProfileTool;
use App\Models\FitnessProfile;
use App\Models\User;

it('returns fitness profile when configured', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->for($user)->generalFitness()->create([
        'goal_details' => 'Stay healthy',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetFitnessProfileTool::class);

    $response->assertOk()
        ->assertSee('General Fitness')
        ->assertSee('Stay healthy')
        ->assertSee('4')
        ->assertSee('60');
});

it('returns not configured message when no fitness profile exists', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(GetFitnessProfileTool::class);

    $response->assertOk()
        ->assertSee('No fitness profile configured yet');
});
