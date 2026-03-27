<?php

declare(strict_types=1);

use App\Actions\ImportGarminActivity;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\User;
use Tests\Support\FitActivityFixtureBuilder;

it('applies exercise mappings when importing a new workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $squatExercise = Exercise::factory()->create(['name' => 'Wall Ball Squat and Press']);
    $shoulderPressExercise = Exercise::factory()->create(['name' => 'Dumbbell Shoulder Press']);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600)
        ->addSet(setType: 1, repetitions: 15, weight: 6.0)
        ->addSet(setType: 1, repetitions: 15, weight: 6.0)
        ->addSet(setType: 0, duration: 60)
        ->addSet(setType: 1, repetitions: 12, weight: 14.0)
        ->addSet(setType: 1, repetitions: 12, weight: 14.0)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute(
        user: $user,
        fitData: $fitData,
        exerciseMappings: [
            0 => $squatExercise->id,
            1 => $shoulderPressExercise->id,
        ],
    );

    $blocks = $result->workout->sections->first()->blocks;
    $exercises = $blocks->flatMap(fn ($b) => $b->exercises);

    $first = $exercises->first();
    expect($first->name)->toBe('Wall Ball Squat and Press')
        ->and($first->exercise_id)->toBe($squatExercise->id);

    $second = $exercises->last();
    expect($second->name)->toBe('Dumbbell Shoulder Press')
        ->and($second->exercise_id)->toBe($shoulderPressExercise->id);

    expect($result->matchedExercises)->toContain('Wall Ball Squat and Press')
        ->and($result->matchedExercises)->toContain('Dumbbell Shoulder Press');
});

it('uses generic names when no exercise mappings provided', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800)
        ->addSet(setType: 1, repetitions: 10, weight: 6.0)
        ->addSet(setType: 1, repetitions: 10, weight: 6.0)
        ->addSet(setType: 0, duration: 60)
        ->addSet(setType: 1, repetitions: 12, weight: 14.0)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute(user: $user, fitData: $fitData);

    $exercises = $result->workout->sections->first()->blocks->flatMap(fn ($b) => $b->exercises);

    expect($exercises[0]->name)->toBe('Exercise 1')
        ->and($exercises[0]->exercise_id)->toBeNull();
    expect($exercises[1]->name)->toBe('Exercise 2')
        ->and($exercises[1]->exercise_id)->toBeNull();
});

it('applies partial exercise mappings', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $exercise = Exercise::factory()->create(['name' => 'Squat']);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800)
        ->addSet(setType: 1, repetitions: 10, weight: 6.0)
        ->addSet(setType: 0, duration: 60)
        ->addSet(setType: 1, repetitions: 12, weight: 14.0)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute(
        user: $user,
        fitData: $fitData,
        exerciseMappings: [0 => $exercise->id],
    );

    $exercises = $result->workout->sections->first()->blocks->flatMap(fn ($b) => $b->exercises);

    expect($exercises[0]->name)->toBe('Squat')
        ->and($exercises[0]->exercise_id)->toBe($exercise->id);
    expect($exercises[1]->name)->toBe('Exercise 2')
        ->and($exercises[1]->exercise_id)->toBeNull();
});

it('applies exercise mappings when merging into existing workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);

    $createAction = app(\App\Actions\CreateStructuredWorkout::class);
    $workout = $createAction->execute(
        user: $user,
        name: 'Strength Day',
        activity: \App\Enums\Workout\Activity::Strength,
        scheduledAt: \Carbon\CarbonImmutable::now(),
        notes: null,
        sections: collect([
            new \App\DataTransferObjects\Workout\SectionData(
                name: 'Main',
                order: 0,
                blocks: collect([
                    new \App\DataTransferObjects\Workout\BlockData(
                        blockType: \App\Enums\Workout\BlockType::StraightSets,
                        order: 0,
                        exercises: collect([
                            new \App\DataTransferObjects\Workout\ExerciseData(
                                name: 'Bench Press',
                                order: 0,
                                type: \App\Enums\Workout\ExerciseType::Strength,
                                exerciseable: new \App\DataTransferObjects\Workout\StrengthExerciseData(targetSets: 3),
                                exerciseId: $exercise->id,
                            ),
                        ]),
                    ),
                ]),
            ),
        ]),
    );

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute(
        user: $user,
        fitData: $fitData,
        existingWorkout: $workout,
        exerciseMappings: [0 => $exercise->id],
    );

    // Sets should be linked to the planned exercise
    $blockExercise = $workout->fresh()->sections->first()->blocks->first()->exercises->first();
    expect($blockExercise->exercise_id)->toBe($exercise->id);

    $sets = ExerciseSet::where('block_exercise_id', $blockExercise->id)->get();
    expect($sets)->toHaveCount(2);
});
