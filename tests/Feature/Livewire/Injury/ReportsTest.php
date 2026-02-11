<?php

use App\Livewire\Injury\Reports;
use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('renders for the injury owner', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->assertOk();
});

it('forbids creating a report for another user\'s injury', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->call('openReportModal')
        ->set('reportType', 'self_reporting')
        ->set('reportContent', 'Test content')
        ->set('reportedAt', '2026-02-04')
        ->call('saveReport')
        ->assertForbidden();
});

it('forbids deleting a report on another user\'s injury', function () {
    $owner = User::factory()->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($owner)->create();

    $stranger = User::factory()->create();
    actingAs($stranger);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->call('deleteReport', $report->id)
        ->assertForbidden();

    $this->assertModelExists($report->fresh());
});

it('displays existing reports', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create([
        'content' => 'Feeling much better today',
    ]);

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->assertSee('Feeling much better today');
});

it('can add a report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->call('openReportModal')
        ->set('reportType', 'self_reporting')
        ->set('reportContent', 'Pain has decreased significantly.')
        ->set('reportedAt', '2026-02-04')
        ->call('saveReport')
        ->assertSet('showReportModal', false);

    assertDatabaseHas('injury_reports', [
        'injury_id' => $injury->id,
        'user_id' => $user->id,
        'type' => 'self_reporting',
        'content' => 'Pain has decreased significantly.',
        'reported_at' => '2026-02-04 00:00:00',
    ]);
});

it('validates required fields when adding a report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->call('openReportModal')
        ->call('saveReport')
        ->assertHasErrors(['reportType', 'reportContent']);
});

it('can delete a report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create();

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->call('deleteReport', $report->id);

    assertDatabaseMissing('injury_reports', ['id' => $report->id]);
});

it('cannot delete a report from another injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();
    $otherInjury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($otherInjury)->for($user)->create();

    actingAs($user);

    Livewire::test(Reports::class, ['injury' => $injury])
        ->call('deleteReport', $report->id);

    assertDatabaseHas('injury_reports', ['id' => $report->id]);
});
