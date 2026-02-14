<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\ListInjuryReportsTool;
use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;

it('lists reports for an injury', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();
    InjuryReport::factory()->for($injury)->for($user)->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuryReportsTool::class, [
        'injury_id' => $injury->id,
    ]);

    $response->assertOk()
        ->assertSee('"total": 3');
});

it('filters reports by type', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();
    InjuryReport::factory()->for($injury)->for($user)->selfReporting()->count(2)->create();
    InjuryReport::factory()->for($injury)->for($user)->ptVisit()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuryReportsTool::class, [
        'injury_id' => $injury->id,
        'type' => 'self_reporting',
    ]);

    $response->assertOk()
        ->assertSee('"total": 2');
});

it('respects limit parameter', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();
    InjuryReport::factory()->for($injury)->for($user)->count(5)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuryReportsTool::class, [
        'injury_id' => $injury->id,
        'limit' => 2,
    ]);

    $response->assertOk()
        ->assertSee('"total": 2');
});

it('returns empty list when no reports exist', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuryReportsTool::class, [
        'injury_id' => $injury->id,
    ]);

    $response->assertOk()
        ->assertSee('"total": 0');
});

it('fails when injury does not belong to user', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuryReportsTool::class, [
        'injury_id' => $injury->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found or access denied.');
});
