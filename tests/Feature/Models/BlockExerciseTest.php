<?php

use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;

it('belongs to a block', function () {
    $strength = StrengthExercise::factory()->create();
    $exercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    expect($exercise->block)->toBeInstanceOf(Block::class);
});

it('morphs to strength exercise', function () {
    $strength = StrengthExercise::factory()->create(['target_sets' => 4, 'target_reps_max' => 12]);
    $exercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    expect($exercise->exerciseable)->toBeInstanceOf(StrengthExercise::class)
        ->and($exercise->exerciseable->target_sets)->toBe(4)
        ->and($exercise->exerciseable->target_reps_max)->toBe(12);
});

it('morphs to cardio exercise', function () {
    $cardio = CardioExercise::factory()->create(['target_duration' => 1800]);
    $exercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardio->id,
    ]);

    expect($exercise->exerciseable)->toBeInstanceOf(CardioExercise::class)
        ->and($exercise->exerciseable->target_duration)->toBe(1800);
});

it('morphs to duration exercise', function () {
    $duration = DurationExercise::factory()->create(['target_duration' => 60]);
    $exercise = BlockExercise::factory()->create([
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $duration->id,
    ]);

    expect($exercise->exerciseable)->toBeInstanceOf(DurationExercise::class)
        ->and($exercise->exerciseable->target_duration)->toBe(60);
});
