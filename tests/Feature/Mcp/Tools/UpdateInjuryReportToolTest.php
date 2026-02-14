<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\UpdateInjuryReportTool;
use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('updates report content', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create(['content' => 'Old content']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryReportTool::class, [
        'report_id' => $report->id,
        'content' => 'Updated content',
    ]);

    $response->assertOk()
        ->assertSee('Injury report updated successfully')
        ->assertSee('Updated content');

    assertDatabaseHas('injury_reports', [
        'id' => $report->id,
        'content' => 'Updated content',
    ]);
});

it('fails when user is not the author', function () {
    $owner = User::factory()->withTimezone('UTC')->create();
    $author = User::factory()->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($author)->create();

    $response = WorkoutServer::actingAs($owner)->tool(UpdateInjuryReportTool::class, [
        'report_id' => $report->id,
        'content' => 'Trying to update',
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot update report. Access denied.');
});

it('fails with non-existent report id', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryReportTool::class, [
        'report_id' => 99999,
        'content' => 'Trying to update',
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury report not found');
});
