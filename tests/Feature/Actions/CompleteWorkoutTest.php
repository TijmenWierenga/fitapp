<?php

use App\Actions\CompleteWorkout;
use App\DataTransferObjects\Workout\PainScore;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('completes workout with rpe and feeling without pain scores', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    app(CompleteWorkout::class)->execute($user, $workout, 7, 4);

    $workout->refresh();
    expect($workout->isCompleted())->toBeTrue()
        ->and($workout->rpe)->toBe(7)
        ->and($workout->feeling)->toBe(4)
        ->and($workout->painScores)->toBeEmpty();
});

it('saves pain scores for active injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();
    $injury = Injury::factory()->active()->for($user)->create();

    app(CompleteWorkout::class)->execute(
        $user,
        $workout,
        6,
        3,
        new PainScore($injury->id, 5),
    );

    $workout->refresh();
    expect($workout->isCompleted())->toBeTrue();

    assertDatabaseHas('workout_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'pain_score' => 5,
    ]);
});

it('ignores pain scores for resolved injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();
    $resolvedInjury = Injury::factory()->resolved()->for($user)->create();

    app(CompleteWorkout::class)->execute(
        $user,
        $workout,
        6,
        3,
        new PainScore($resolvedInjury->id, 5),
    );

    assertDatabaseMissing('workout_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $resolvedInjury->id,
    ]);
});

it('ignores pain scores for injuries belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();
    $otherInjury = Injury::factory()->active()->for($otherUser)->create();

    app(CompleteWorkout::class)->execute(
        $user,
        $workout,
        6,
        3,
        new PainScore($otherInjury->id, 5),
    );

    assertDatabaseMissing('workout_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $otherInjury->id,
    ]);
});
