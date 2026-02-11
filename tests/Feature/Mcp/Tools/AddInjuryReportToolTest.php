<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\AddInjuryReportTool;
use App\Models\Injury;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('adds a self_reporting report to an injury', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => $injury->id,
        'type' => 'self_reporting',
        'content' => 'Knee feeling better today after rest.',
    ]);

    $response->assertOk()
        ->assertSee('Injury report added successfully')
        ->assertSee('self_reporting')
        ->assertSee('Knee feeling better today after rest.');

    assertDatabaseHas('injury_reports', [
        'injury_id' => $injury->id,
        'user_id' => $user->id,
        'type' => 'self_reporting',
    ]);
});

it('adds a pt_visit report', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => $injury->id,
        'type' => 'pt_visit',
        'content' => 'PT recommended strengthening exercises.',
    ]);

    $response->assertOk()
        ->assertSee('pt_visit');
});

it('adds a milestone report', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => $injury->id,
        'type' => 'milestone',
        'content' => 'First pain-free run!',
    ]);

    $response->assertOk()
        ->assertSee('milestone');
});

it('fails with invalid report type', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => $injury->id,
        'type' => 'invalid_type',
        'content' => 'Some content.',
    ]);

    $response->assertHasErrors()
        ->assertSee('Invalid report type');
});

it('fails when injury does not belong to user', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => $injury->id,
        'type' => 'self_reporting',
        'content' => 'Some content.',
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found or does not belong to this user');
});

it('fails with non-existent injury id', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => 99999,
        'type' => 'self_reporting',
        'content' => 'Some content.',
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found or does not belong to this user');
});

it('fails without content', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(AddInjuryReportTool::class, [
        'injury_id' => $injury->id,
        'type' => 'self_reporting',
    ]);

    $response->assertHasErrors();
});
