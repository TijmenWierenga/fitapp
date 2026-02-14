<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\DeleteInjuryReportTool;
use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;

use function Pest\Laravel\assertDatabaseMissing;

it('allows author to delete their report', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(DeleteInjuryReportTool::class, [
        'report_id' => $report->id,
    ]);

    $response->assertOk()
        ->assertSee('Injury report deleted successfully');

    assertDatabaseMissing('injury_reports', ['id' => $report->id]);
});

it('allows injury owner to delete any report', function () {
    $owner = User::factory()->withTimezone('UTC')->create();
    $author = User::factory()->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($author)->create();

    $response = WorkoutServer::actingAs($owner)->tool(DeleteInjuryReportTool::class, [
        'report_id' => $report->id,
    ]);

    $response->assertOk()
        ->assertSee('Injury report deleted successfully');

    assertDatabaseMissing('injury_reports', ['id' => $report->id]);
});

it('denies non-owner non-author from deleting', function () {
    $owner = User::factory()->create();
    $author = User::factory()->create();
    $stranger = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($author)->create();

    $response = WorkoutServer::actingAs($stranger)->tool(DeleteInjuryReportTool::class, [
        'report_id' => $report->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot delete report. Access denied.');
});

it('fails with non-existent report id', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(DeleteInjuryReportTool::class, [
        'report_id' => 99999,
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury report not found');
});
