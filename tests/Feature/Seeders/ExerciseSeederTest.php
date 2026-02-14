<?php

use App\Models\Exercise;
use App\Models\MuscleGroup;
use Database\Seeders\ExerciseSeeder;

it('seeds muscle groups', function (): void {
    $this->seed(ExerciseSeeder::class);

    expect(MuscleGroup::count())->toBe(17);

    $chest = MuscleGroup::where('name', 'chest')->first();
    expect($chest)
        ->label->toBe('Chest')
        ->body_part->toBe(\App\Enums\BodyPart::Chest);

    $lats = MuscleGroup::where('name', 'lats')->first();
    expect($lats)
        ->label->toBe('Lats')
        ->body_part->toBe(\App\Enums\BodyPart::UpperBack);
});

it('seeds exercises with muscle group relationships', function (): void {
    $this->seed(ExerciseSeeder::class);

    expect(Exercise::count())->toBeGreaterThan(800);

    $benchPress = Exercise::where('name', 'Barbell Bench Press - Medium Grip')->first();
    expect($benchPress)
        ->not->toBeNull()
        ->force->toBe('push')
        ->category->toBe('strength');

    $primaryMuscles = $benchPress->primaryMuscles;
    expect($primaryMuscles)->toHaveCount(1);
    expect($primaryMuscles->first()->name)->toBe('chest');

    $secondaryMuscles = $benchPress->secondaryMuscles;
    expect($secondaryMuscles->pluck('name')->sort()->values()->toArray())
        ->toBe(['shoulders', 'triceps']);
});

it('is idempotent', function (): void {
    $this->seed(ExerciseSeeder::class);
    $firstMuscleCount = MuscleGroup::count();
    $firstExerciseCount = Exercise::count();
    $firstPivotCount = \DB::table('exercise_muscle_group')->count();

    $this->seed(ExerciseSeeder::class);

    expect(MuscleGroup::count())->toBe($firstMuscleCount);
    expect(Exercise::count())->toBe($firstExerciseCount);
    expect(\DB::table('exercise_muscle_group')->count())->toBe($firstPivotCount);
});
