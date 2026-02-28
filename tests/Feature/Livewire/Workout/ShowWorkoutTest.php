<?php

use App\Enums\BodyPart;
use App\Livewire\Workout\Show;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutPainScore;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('saves pain scores when submitting evaluation with active injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()]);
    $injury = Injury::factory()->active()->for($user)->create(['body_part' => BodyPart::Knee]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->set("painScores.{$injury->id}", 5)
        ->call('submitEvaluation')
        ->assertDispatched('workout-completed');

    assertDatabaseHas('workout_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'pain_score' => 5,
    ]);
});

it('completes without pain scores when user has no injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 6)
        ->set('feeling', 3)
        ->call('submitEvaluation')
        ->assertDispatched('workout-completed');

    $workout->refresh();
    expect($workout->isCompleted())->toBeTrue()
        ->and($workout->painScores)->toBeEmpty();
});

it('displays pain scores on completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $injury = Injury::factory()->active()->for($user)->create(['body_part' => BodyPart::Knee]);
    WorkoutPainScore::factory()->create([
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'pain_score' => 5,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Pain Assessment')
        ->assertSee('Knee')
        ->assertSee('5/10')
        ->assertSee('Moderate');
});
