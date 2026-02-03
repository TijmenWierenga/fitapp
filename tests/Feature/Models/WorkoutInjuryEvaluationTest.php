<?php

use App\Models\Injury;
use App\Models\Workout;
use App\Models\WorkoutInjuryEvaluation;

it('belongs to a workout', function () {
    $workout = Workout::factory()->create();
    $evaluation = WorkoutInjuryEvaluation::factory()->create(['workout_id' => $workout->id]);

    expect($evaluation->workout)->toBeInstanceOf(Workout::class);
    expect($evaluation->workout->id)->toBe($workout->id);
});

it('belongs to an injury', function () {
    $injury = Injury::factory()->create();
    $evaluation = WorkoutInjuryEvaluation::factory()->create(['injury_id' => $injury->id]);

    expect($evaluation->injury)->toBeInstanceOf(Injury::class);
    expect($evaluation->injury->id)->toBe($injury->id);
});

it('trims whitespace from notes', function () {
    $evaluation = WorkoutInjuryEvaluation::factory()->create(['notes' => '  Some note with spaces  ']);

    expect($evaluation->notes)->toBe('Some note with spaces');
});

it('converts empty string notes to null', function () {
    $evaluation = WorkoutInjuryEvaluation::factory()->create(['notes' => '   ']);

    expect($evaluation->notes)->toBeNull();
});

it('casts discomfort_score to integer', function () {
    $evaluation = WorkoutInjuryEvaluation::factory()->create(['discomfort_score' => 5]);

    expect($evaluation->discomfort_score)->toBeInt();
    expect($evaluation->discomfort_score)->toBe(5);
});

it('allows null discomfort_score', function () {
    $evaluation = WorkoutInjuryEvaluation::factory()->create(['discomfort_score' => null]);

    expect($evaluation->discomfort_score)->toBeNull();
});
