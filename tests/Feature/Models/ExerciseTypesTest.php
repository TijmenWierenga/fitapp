<?php

use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;

it('creates strength exercise with all fields', function () {
    $exercise = StrengthExercise::create([
        'target_sets' => 4,
        'target_reps_min' => 8,
        'target_reps_max' => 12,
        'target_weight' => 80.50,
        'target_rpe' => 7.5,
        'target_tempo' => '3-1-1-0',
        'rest_after' => 90,
    ]);

    $exercise->refresh();

    expect($exercise->target_sets)->toBe(4)
        ->and($exercise->target_reps_min)->toBe(8)
        ->and($exercise->target_reps_max)->toBe(12)
        ->and((float) $exercise->target_weight)->toBe(80.50)
        ->and((float) $exercise->target_rpe)->toBe(7.5)
        ->and($exercise->target_tempo)->toBe('3-1-1-0')
        ->and($exercise->rest_after)->toBe(90);
});

it('creates cardio exercise with all fields', function () {
    $exercise = CardioExercise::create([
        'target_duration' => 3600,
        'target_distance' => 10000.00,
        'target_pace_min' => 270,
        'target_pace_max' => 300,
        'target_heart_rate_zone' => 3,
        'target_heart_rate_min' => 140,
        'target_heart_rate_max' => 155,
        'target_power' => 250,
    ]);

    $exercise->refresh();

    expect($exercise->target_duration)->toBe(3600)
        ->and((float) $exercise->target_distance)->toBe(10000.00)
        ->and($exercise->target_pace_min)->toBe(270)
        ->and($exercise->target_pace_max)->toBe(300)
        ->and($exercise->target_heart_rate_zone)->toBe(3)
        ->and($exercise->target_heart_rate_min)->toBe(140)
        ->and($exercise->target_heart_rate_max)->toBe(155)
        ->and($exercise->target_power)->toBe(250);
});

it('creates duration exercise with all fields', function () {
    $exercise = DurationExercise::create([
        'target_duration' => 60,
        'target_rpe' => 5.0,
    ]);

    $exercise->refresh();

    expect($exercise->target_duration)->toBe(60)
        ->and((float) $exercise->target_rpe)->toBe(5.0);
});

it('strength exercise has blockExercise morph relationship', function () {
    $strength = StrengthExercise::factory()->create();
    $blockExercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    expect($strength->blockExercise)->toBeInstanceOf(BlockExercise::class)
        ->and($strength->blockExercise->id)->toBe($blockExercise->id);
});

it('cardio exercise has blockExercise morph relationship', function () {
    $cardio = CardioExercise::factory()->create();
    $blockExercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardio->id,
    ]);

    expect($cardio->blockExercise)->toBeInstanceOf(BlockExercise::class)
        ->and($cardio->blockExercise->id)->toBe($blockExercise->id);
});

it('duration exercise has blockExercise morph relationship', function () {
    $duration = DurationExercise::factory()->create();
    $blockExercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $duration->id,
    ]);

    expect($duration->blockExercise)->toBeInstanceOf(BlockExercise::class)
        ->and($duration->blockExercise->id)->toBe($blockExercise->id);
});
