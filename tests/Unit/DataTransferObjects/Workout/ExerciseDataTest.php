<?php

use App\DataTransferObjects\Workout\CardioExerciseData;
use App\DataTransferObjects\Workout\DurationExerciseData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Workout\ExerciseType;

it('maps strength type to StrengthExerciseData', function () {
    $exercise = ExerciseData::fromArray([
        'name' => 'Bench Press',
        'order' => 0,
        'type' => 'strength',
        'notes' => 'Go heavy',
        'target_sets' => 4,
        'target_reps_min' => 6,
        'target_reps_max' => 8,
        'target_weight' => 100.0,
        'target_rpe' => 8.5,
        'target_tempo' => '3010',
        'rest_after' => 120,
    ]);

    expect($exercise->name)->toBe('Bench Press')
        ->and($exercise->order)->toBe(0)
        ->and($exercise->type)->toBe(ExerciseType::Strength)
        ->and($exercise->notes)->toBe('Go heavy')
        ->and($exercise->exerciseable)->toBeInstanceOf(StrengthExerciseData::class)
        ->and($exercise->exerciseable->targetSets)->toBe(4)
        ->and($exercise->exerciseable->targetRepsMin)->toBe(6)
        ->and($exercise->exerciseable->targetRepsMax)->toBe(8)
        ->and($exercise->exerciseable->targetWeight)->toBe(100.0)
        ->and($exercise->exerciseable->targetRpe)->toBe(8.5)
        ->and($exercise->exerciseable->targetTempo)->toBe('3010')
        ->and($exercise->exerciseable->restAfter)->toBe(120);
});

it('maps cardio type to CardioExerciseData', function () {
    $exercise = ExerciseData::fromArray([
        'name' => 'Treadmill Run',
        'order' => 0,
        'type' => 'cardio',
        'target_duration' => 1800,
        'target_distance' => 5000.0,
        'target_pace_min' => 330,
        'target_pace_max' => 360,
        'target_heart_rate_zone' => 3,
        'target_heart_rate_min' => 140,
        'target_heart_rate_max' => 160,
        'target_power' => 250,
    ]);

    expect($exercise->type)->toBe(ExerciseType::Cardio)
        ->and($exercise->exerciseable)->toBeInstanceOf(CardioExerciseData::class)
        ->and($exercise->exerciseable->targetDuration)->toBe(1800)
        ->and($exercise->exerciseable->targetDistance)->toBe(5000.0)
        ->and($exercise->exerciseable->targetPaceMin)->toBe(330)
        ->and($exercise->exerciseable->targetPaceMax)->toBe(360)
        ->and($exercise->exerciseable->targetHeartRateZone)->toBe(3)
        ->and($exercise->exerciseable->targetHeartRateMin)->toBe(140)
        ->and($exercise->exerciseable->targetHeartRateMax)->toBe(160)
        ->and($exercise->exerciseable->targetPower)->toBe(250);
});

it('maps duration type to DurationExerciseData', function () {
    $exercise = ExerciseData::fromArray([
        'name' => 'Plank',
        'order' => 0,
        'type' => 'duration',
        'target_duration' => 60,
        'target_rpe' => 7.0,
    ]);

    expect($exercise->type)->toBe(ExerciseType::Duration)
        ->and($exercise->exerciseable)->toBeInstanceOf(DurationExerciseData::class)
        ->and($exercise->exerciseable->targetDuration)->toBe(60)
        ->and($exercise->exerciseable->targetRpe)->toBe(7.0);
});

it('defaults nullable fields to null', function () {
    $exercise = ExerciseData::fromArray([
        'name' => 'Simple Exercise',
        'order' => 0,
        'type' => 'strength',
    ]);

    expect($exercise->notes)->toBeNull()
        ->and($exercise->exerciseable)->toBeInstanceOf(StrengthExerciseData::class)
        ->and($exercise->exerciseable->targetSets)->toBeNull()
        ->and($exercise->exerciseable->targetRepsMin)->toBeNull()
        ->and($exercise->exerciseable->targetWeight)->toBeNull();
});
