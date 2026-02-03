<?php

use App\Livewire\Workout\Show;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutInjuryEvaluation;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('shows workout details', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Morning Run']);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->assertSee('Morning Run');
});

it('can complete a workout with notes', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set('completionNotes', 'Great workout!')
        ->call('submitEvaluation');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'rpe' => 7,
        'feeling' => 4,
        'completion_notes' => 'Great workout!',
    ]);

    $workout->refresh();
    expect($workout->isCompleted())->toBeTrue();
});

it('can complete a workout with injury evaluations', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 6)
        ->set('feeling', 3)
        ->call('setInjuryDiscomfort', $injury->id, 4)
        ->set("injuryEvaluations.{$injury->id}.notes", 'Felt some discomfort')
        ->call('submitEvaluation');

    assertDatabaseHas('workout_injury_evaluations', [
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'discomfort_score' => 4,
        'notes' => 'Felt some discomfort',
    ]);
});

it('shows active injuries in evaluation modal', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => \App\Enums\BodyPart::Knee,
    ]);
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->assertSee('Knee');
});

it('displays completion notes for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create([
        'completion_notes' => 'Felt really strong today!',
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->assertSee('Completion Notes')
        ->assertSee('Felt really strong today!');
});

it('displays injury evaluations for completed workout', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => \App\Enums\BodyPart::Shoulder,
    ]);
    $workout = Workout::factory()->for($user)->completed()->create();
    WorkoutInjuryEvaluation::factory()->create([
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'discomfort_score' => 5,
        'notes' => 'Moderate pain',
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->assertSee('Injury Feedback')
        ->assertSee('Shoulder')
        ->assertSee('5/10')
        ->assertSee('Moderate pain');
});

it('requires rpe and feeling to submit evaluation', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->call('submitEvaluation')
        ->assertHasErrors(['rpe', 'feeling']);
});

it('resets form when canceling evaluation', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set('completionNotes', 'Some notes')
        ->call('cancelEvaluation')
        ->assertSet('rpe', null)
        ->assertSet('feeling', null)
        ->assertSet('completionNotes', null)
        ->assertSet('showEvaluationModal', false);
});

it('only includes injury evaluations with data', function () {
    $user = User::factory()->create();
    $injury1 = Injury::factory()->for($user)->active()->create();
    $injury2 = Injury::factory()->for($user)->active()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->set('rpe', 6)
        ->set('feeling', 3)
        ->call('setInjuryDiscomfort', $injury1->id, 3)
        ->call('submitEvaluation');

    expect(WorkoutInjuryEvaluation::count())->toBe(1);
    expect(WorkoutInjuryEvaluation::first()->injury_id)->toBe($injury1->id);
});
