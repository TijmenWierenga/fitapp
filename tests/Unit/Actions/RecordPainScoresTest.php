<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

use App\Actions\RecordPainScores;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseHas;

it('records pain scores for multiple injuries', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $injuryA = Injury::factory()->for($user)->active()->create();
    $injuryB = Injury::factory()->for($user)->active()->create();

    $action = new RecordPainScores;

    $action->execute($workout, [
        $injuryA->id => 3,
        $injuryB->id => 7,
    ]);

    assertDatabaseHas('workout_injury_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $injuryA->id,
        'pain_score' => 3,
    ]);

    assertDatabaseHas('workout_injury_pain_scores', [
        'workout_id' => $workout->id,
        'injury_id' => $injuryB->id,
        'pain_score' => 7,
    ]);
});

it('creates correct records in workout_injury_pain_scores table', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();
    $injury = Injury::factory()->for($user)->active()->create();

    $action = new RecordPainScores;

    $action->execute($workout, [
        $injury->id => 5,
    ]);

    expect($workout->painScores)->toHaveCount(1);

    $painScore = $workout->painScores->first();
    expect($painScore->workout_id)->toBe($workout->id)
        ->and($painScore->injury_id)->toBe($injury->id)
        ->and($painScore->pain_score)->toBe(5);
});
