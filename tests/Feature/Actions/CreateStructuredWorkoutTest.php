<?php

use App\Actions\CreateStructuredWorkout;
use App\DataTransferObjects\Workout\BlockData;
use App\DataTransferObjects\Workout\CardioExerciseData;
use App\DataTransferObjects\Workout\DurationExerciseData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\SectionData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseType;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;

it('creates a workout with full nested structure', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $sections = collect([
        new SectionData(
            name: 'Warm-up',
            order: 0,
            blocks: collect([
                new BlockData(
                    blockType: BlockType::DistanceDuration,
                    order: 0,
                    exercises: collect([
                        new ExerciseData(
                            name: 'Light Jog',
                            order: 0,
                            type: ExerciseType::Cardio,
                            exerciseable: new CardioExerciseData(
                                targetDuration: 300,
                            ),
                        ),
                    ]),
                ),
            ]),
            notes: 'Light movement',
        ),
        new SectionData(
            name: 'Main',
            order: 1,
            blocks: collect([
                new BlockData(
                    blockType: BlockType::StraightSets,
                    order: 0,
                    exercises: collect([
                        new ExerciseData(
                            name: 'Bench Press',
                            order: 0,
                            type: ExerciseType::Strength,
                            exerciseable: new StrengthExerciseData(
                                targetSets: 4,
                                targetRepsMin: 8,
                                targetRepsMax: 10,
                                targetWeight: 80.0,
                            ),
                        ),
                        new ExerciseData(
                            name: 'Plank',
                            order: 1,
                            type: ExerciseType::Duration,
                            exerciseable: new DurationExerciseData(
                                targetDuration: 60,
                                targetRpe: 6.0,
                            ),
                        ),
                    ]),
                ),
            ]),
        ),
    ]);

    $workout = app(CreateStructuredWorkout::class)->execute(
        user: $user,
        name: 'Full Body Workout',
        activity: Activity::Strength,
        scheduledAt: CarbonImmutable::parse('2026-02-10 08:00:00'),
        notes: 'Test workout',
        sections: $sections,
    );

    expect($workout)->toBeInstanceOf(Workout::class)
        ->and($workout->name)->toBe('Full Body Workout')
        ->and($workout->sections)->toHaveCount(2);

    $warmup = $workout->sections->firstWhere('name', 'Warm-up');
    expect($warmup->order)->toBe(0)
        ->and($warmup->notes)->toBe('Light movement')
        ->and($warmup->blocks)->toHaveCount(1);

    $warmupBlock = $warmup->blocks->first();
    expect($warmupBlock->block_type->value)->toBe('distance_duration')
        ->and($warmupBlock->exercises)->toHaveCount(1);

    $cardioExercise = $warmupBlock->exercises->first();
    expect($cardioExercise->name)->toBe('Light Jog')
        ->and($cardioExercise->exerciseable)->toBeInstanceOf(CardioExercise::class)
        ->and($cardioExercise->exerciseable->target_duration)->toBe(300);

    $main = $workout->sections->firstWhere('name', 'Main');
    expect($main->order)->toBe(1)
        ->and($main->blocks)->toHaveCount(1);

    $mainBlock = $main->blocks->first();
    expect($mainBlock->exercises)->toHaveCount(2);

    $benchPress = $mainBlock->exercises->firstWhere('name', 'Bench Press');
    expect($benchPress->exerciseable)->toBeInstanceOf(StrengthExercise::class)
        ->and($benchPress->exerciseable->target_sets)->toBe(4)
        ->and($benchPress->exerciseable->target_reps_min)->toBe(8)
        ->and($benchPress->exerciseable->target_reps_max)->toBe(10);

    $plank = $mainBlock->exercises->firstWhere('name', 'Plank');
    expect($plank->exerciseable)->toBeInstanceOf(DurationExercise::class)
        ->and($plank->exerciseable->target_duration)->toBe(60);
});

it('creates a workout with empty sections', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $sections = collect([
        new SectionData(
            name: 'Main',
            order: 0,
            blocks: collect(),
        ),
    ]);

    $workout = app(CreateStructuredWorkout::class)->execute(
        user: $user,
        name: 'Simple Workout',
        activity: Activity::Run,
        scheduledAt: CarbonImmutable::parse('2026-02-10 08:00:00'),
        notes: null,
        sections: $sections,
    );

    expect($workout->sections)->toHaveCount(1)
        ->and($workout->sections->first()->blocks)->toHaveCount(0);
});

it('creates correct database records', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $sections = collect([
        new SectionData(
            name: 'Main',
            order: 0,
            blocks: collect([
                new BlockData(
                    blockType: BlockType::Circuit,
                    order: 0,
                    exercises: collect([
                        new ExerciseData(
                            name: 'Push-up',
                            order: 0,
                            type: ExerciseType::Strength,
                            exerciseable: new StrengthExerciseData(
                                targetSets: 1,
                                targetRepsMax: 20,
                            ),
                        ),
                    ]),
                    rounds: 3,
                    restBetweenExercises: 30,
                ),
            ]),
        ),
    ]);

    app(CreateStructuredWorkout::class)->execute(
        user: $user,
        name: 'DB Test',
        activity: Activity::Strength,
        scheduledAt: CarbonImmutable::parse('2026-02-10 08:00:00'),
        notes: null,
        sections: $sections,
    );

    $this->assertDatabaseHas('sections', ['name' => 'Main', 'order' => 0]);
    $this->assertDatabaseHas('blocks', ['block_type' => 'circuit', 'rounds' => 3, 'rest_between_exercises' => 30]);
    $this->assertDatabaseHas('block_exercises', ['name' => 'Push-up', 'order' => 0]);
    $this->assertDatabaseHas('strength_exercises', ['target_sets' => 1, 'target_reps_max' => 20]);
});
