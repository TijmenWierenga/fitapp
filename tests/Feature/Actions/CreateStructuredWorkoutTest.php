<?php

use App\Actions\CreateStructuredWorkout;
use App\Enums\Workout\Activity;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;

it('creates a workout with full nested structure', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $workout = app(CreateStructuredWorkout::class)->execute(
        user: $user,
        name: 'Full Body Workout',
        activity: Activity::Strength,
        scheduledAt: CarbonImmutable::parse('2026-02-10 08:00:00'),
        notes: 'Test workout',
        sections: [
            [
                'name' => 'Warm-up',
                'order' => 0,
                'notes' => 'Light movement',
                'blocks' => [
                    [
                        'block_type' => 'distance_duration',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Light Jog',
                                'order' => 0,
                                'type' => 'cardio',
                                'target_duration' => 300,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Main',
                'order' => 1,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Bench Press',
                                'order' => 0,
                                'type' => 'strength',
                                'target_sets' => 4,
                                'target_reps_min' => 8,
                                'target_reps_max' => 10,
                                'target_weight' => 80.0,
                            ],
                            [
                                'name' => 'Plank',
                                'order' => 1,
                                'type' => 'duration',
                                'target_duration' => 60,
                                'target_rpe' => 6.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
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

    $workout = app(CreateStructuredWorkout::class)->execute(
        user: $user,
        name: 'Simple Workout',
        activity: Activity::Run,
        scheduledAt: CarbonImmutable::parse('2026-02-10 08:00:00'),
        notes: null,
        sections: [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [],
            ],
        ],
    );

    expect($workout->sections)->toHaveCount(1)
        ->and($workout->sections->first()->blocks)->toHaveCount(0);
});

it('creates correct database records', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    app(CreateStructuredWorkout::class)->execute(
        user: $user,
        name: 'DB Test',
        activity: Activity::Strength,
        scheduledAt: CarbonImmutable::parse('2026-02-10 08:00:00'),
        notes: null,
        sections: [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'circuit',
                        'order' => 0,
                        'rounds' => 3,
                        'rest_between_exercises' => 30,
                        'exercises' => [
                            [
                                'name' => 'Push-up',
                                'order' => 0,
                                'type' => 'strength',
                                'target_sets' => 1,
                                'target_reps_max' => 20,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    );

    $this->assertDatabaseHas('sections', ['name' => 'Main', 'order' => 0]);
    $this->assertDatabaseHas('blocks', ['block_type' => 'circuit', 'rounds' => 3, 'rest_between_exercises' => 30]);
    $this->assertDatabaseHas('block_exercises', ['name' => 'Push-up', 'order' => 0]);
    $this->assertDatabaseHas('strength_exercises', ['target_sets' => 1, 'target_reps_max' => 20]);
});
