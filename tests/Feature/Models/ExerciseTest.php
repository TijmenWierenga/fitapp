<?php

use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\MuscleGroup;

it('has muscle groups relationship', function (): void {
    $exercise = Exercise::factory()->create();
    $muscleGroup = MuscleGroup::factory()->create();

    $exercise->muscleGroups()->attach($muscleGroup, ['load_factor' => 1.0]);

    expect($exercise->muscleGroups)->toHaveCount(1);
    expect((float) $exercise->muscleGroups->first()->pivot->load_factor)->toBe(1.0);
});

it('has primary muscles relationship', function (): void {
    $exercise = Exercise::factory()->create();
    $primary = MuscleGroup::factory()->create();
    $secondary = MuscleGroup::factory()->create();

    $exercise->muscleGroups()->attach($primary, ['load_factor' => 1.0]);
    $exercise->muscleGroups()->attach($secondary, ['load_factor' => 0.5]);

    expect($exercise->primaryMuscles)->toHaveCount(1);
    expect($exercise->primaryMuscles->first()->id)->toBe($primary->id);
});

it('has secondary muscles relationship', function (): void {
    $exercise = Exercise::factory()->create();
    $primary = MuscleGroup::factory()->create();
    $secondary = MuscleGroup::factory()->create();

    $exercise->muscleGroups()->attach($primary, ['load_factor' => 1.0]);
    $exercise->muscleGroups()->attach($secondary, ['load_factor' => 0.5]);

    expect($exercise->secondaryMuscles)->toHaveCount(1);
    expect($exercise->secondaryMuscles->first()->id)->toBe($secondary->id);
});

it('has block exercises relationship', function (): void {
    $exercise = Exercise::factory()->create();
    $strength = \App\Models\StrengthExercise::factory()->create();
    $blockExercise = BlockExercise::factory()->create([
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    expect($exercise->blockExercises)->toHaveCount(1);
    expect($exercise->blockExercises->first()->id)->toBe($blockExercise->id);
});

it('casts json fields', function (): void {
    $exercise = Exercise::factory()->create([
        'instructions' => ['Step 1', 'Step 2'],
        'aliases' => ['Alt name'],
        'tips' => ['Tip 1'],
    ]);

    $exercise->refresh();

    expect($exercise->instructions)->toBe(['Step 1', 'Step 2']);
    expect($exercise->aliases)->toBe(['Alt name']);
    expect($exercise->tips)->toBe(['Tip 1']);
});
