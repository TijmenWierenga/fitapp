<?php

use App\Livewire\Workout\Complete;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutInjuryEvaluation;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('opens modal when mark-workout-complete event is dispatched', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Evening Run']);

    actingAs($user);

    Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->assertSet('workout.id', $workout->id);
});

it('can complete a workout via modal', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set('completionNotes', 'Great session!')
        ->call('submit')
        ->assertDispatched('workout-completed');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'rpe' => 7,
        'feeling' => 4,
        'completion_notes' => 'Great session!',
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

    Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id)
        ->set('rpe', 6)
        ->set('feeling', 3)
        ->call('setInjuryDiscomfort', $injury->id, 5)
        ->set("injuryEvaluations.{$injury->id}.notes", 'Some discomfort')
        ->call('submit');

    assertDatabaseHas('workout_injury_evaluations', [
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'discomfort_score' => 5,
        'notes' => 'Some discomfort',
    ]);
});

it('requires rpe and feeling to submit', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id)
        ->call('submit')
        ->assertHasErrors(['rpe', 'feeling']);
});

it('resets form when closing modal', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set('completionNotes', 'Some notes')
        ->call('closeModal')
        ->assertSet('rpe', null)
        ->assertSet('feeling', null)
        ->assertSet('completionNotes', null)
        ->assertSet('showModal', false)
        ->assertSet('workout', null);
});

it('initializes injury evaluations when opening modal', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    $component = Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id);

    expect($component->get('injuryEvaluations'))->toHaveKey($injury->id);
});

it('only creates injury evaluations with data', function () {
    $user = User::factory()->create();
    $injury1 = Injury::factory()->for($user)->active()->create();
    $injury2 = Injury::factory()->for($user)->active()->create();
    $workout = Workout::factory()->for($user)->state([
        'scheduled_at' => now()->subHour(),
    ])->create();

    actingAs($user);

    Livewire::test(Complete::class)
        ->dispatch('mark-workout-complete', workoutId: $workout->id)
        ->set('rpe', 6)
        ->set('feeling', 3)
        ->call('setInjuryDiscomfort', $injury1->id, 3)
        ->call('submit');

    expect(WorkoutInjuryEvaluation::count())->toBe(1);
    expect(WorkoutInjuryEvaluation::first()->injury_id)->toBe($injury1->id);
});
