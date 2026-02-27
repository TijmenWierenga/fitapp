<?php

use App\Enums\BodyPart;
use App\Enums\Severity;
use App\Livewire\Workout\Show;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('shows pain check section when user has active injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
        'severity' => Severity::Moderate,
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->assertSet('showEvaluationModal', true)
        ->assertSee('Pain Check')
        ->assertSee('Knee');
});

it('hides pain check section when user has no active injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->assertSet('showEvaluationModal', true)
        ->assertDontSee('Pain Check');
});

it('records pain scores when completing a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set("painScores.{$injury->id}", 5)
        ->call('submitEvaluation')
        ->assertSet('showEvaluationModal', false);

    assertDatabaseHas('workout_injury_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'pain_score' => 5,
    ]);

    $workout->refresh();
    expect($workout->completed_at)->not->toBeNull();
});

it('completes workout without pain scores when pain scores are left empty', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);
    $injury = Injury::factory()->for($user)->active()->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 6)
        ->set('feeling', 3)
        ->call('submitEvaluation')
        ->assertSet('showEvaluationModal', false);

    $workout->refresh();
    expect($workout->completed_at)->not->toBeNull()
        ->and($workout->painScores)->toHaveCount(0);
});

it('validates pain score range', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);
    $injury = Injury::factory()->for($user)->active()->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set("painScores.{$injury->id}", 11)
        ->call('submitEvaluation')
        ->assertHasErrors(["painScores.{$injury->id}"]);
});

it('records pain scores for multiple injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);
    $kneeInjury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
    ]);
    $shoulderInjury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Shoulder,
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 8)
        ->set('feeling', 3)
        ->set("painScores.{$kneeInjury->id}", 4)
        ->set("painScores.{$shoulderInjury->id}", 6)
        ->call('submitEvaluation')
        ->assertSet('showEvaluationModal', false);

    assertDatabaseHas('workout_injury_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $kneeInjury->id,
        'pain_score' => 4,
    ]);

    assertDatabaseHas('workout_injury_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $shoulderInjury->id,
        'pain_score' => 6,
    ]);
});

it('displays pain scores on completed workout detail view', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
    ]);

    \App\Models\WorkoutInjuryPainScore::factory()->create([
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'pain_score' => 6,
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->assertSee('Pain Check')
        ->assertSee('Knee')
        ->assertSee('6/10');
});
