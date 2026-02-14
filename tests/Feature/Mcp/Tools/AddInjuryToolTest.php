<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\AddInjuryTool;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('adds an injury successfully', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryTool::class, [
        'injury_type' => 'acute',
        'body_part' => 'knee',
        'started_at' => '2024-01-15',
        'notes' => 'Running injury',
    ]);

    $response->assertOk()
        ->assertSee('knee')
        ->assertSee('acute')
        ->assertSee('Injury added successfully');

    assertDatabaseHas('injuries', [
        'user_id' => $user->id,
        'injury_type' => 'acute',
        'body_part' => 'knee',
        'notes' => 'Running injury',
    ]);
});

it('adds a resolved injury with end date', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryTool::class, [
        'injury_type' => 'chronic',
        'body_part' => 'lower_back',
        'started_at' => '2023-06-01',
        'ended_at' => '2024-01-01',
    ]);

    $response->assertOk()
        ->assertSee('"is_active": false');

    $injury = $user->injuries()->first();
    expect($injury->ended_at->toDateString())->toBe('2024-01-01');
});

it('fails with invalid injury_type', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryTool::class, [
        'injury_type' => 'invalid_type',
        'body_part' => 'knee',
        'started_at' => '2024-01-15',
    ]);

    $response->assertHasErrors();
});

it('fails with invalid body_part', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryTool::class, [
        'injury_type' => 'acute',
        'body_part' => 'invalid_part',
        'started_at' => '2024-01-15',
    ]);

    $response->assertHasErrors();
});

it('fails when end date is before start date', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryTool::class, [
        'injury_type' => 'acute',
        'body_part' => 'knee',
        'started_at' => '2024-06-01',
        'ended_at' => '2024-01-01',
    ]);

    $response->assertHasErrors();
});
