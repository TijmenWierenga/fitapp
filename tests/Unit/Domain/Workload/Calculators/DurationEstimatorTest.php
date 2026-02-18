<?php

use App\Domain\Workload\Calculators\DurationEstimator;
use App\Domain\Workload\ValueObjects\PlannedBlock;
use App\Enums\Workout\BlockType;

beforeEach(function (): void {
    $this->estimator = new DurationEstimator;
});

function plannedBlock(
    BlockType $blockType,
    array $exerciseDurations = [],
    ?int $rounds = null,
    ?int $restBetweenExercises = null,
    ?int $restBetweenRounds = null,
    ?int $timeCap = null,
    ?int $workInterval = null,
    ?int $restInterval = null,
): PlannedBlock {
    return new PlannedBlock(
        blockType: $blockType,
        rounds: $rounds,
        restBetweenExercises: $restBetweenExercises,
        restBetweenRounds: $restBetweenRounds,
        timeCap: $timeCap,
        workInterval: $workInterval,
        restInterval: $restInterval,
        exerciseDurations: $exerciseDurations,
    );
}

it('estimates distance_duration as sum of exercise durations', function (): void {
    $blocks = [
        plannedBlock(BlockType::DistanceDuration, exerciseDurations: [1800, 600]),
    ];

    expect($this->estimator->estimate($blocks))->toBe(2400);
});

it('estimates interval as rounds * (work + rest) - rest', function (): void {
    // 5 rounds * (30 + 10) - 10 = 190
    $blocks = [
        plannedBlock(BlockType::Interval, rounds: 5, workInterval: 30, restInterval: 10),
    ];

    expect($this->estimator->estimate($blocks))->toBe(190);
});

it('estimates amrap as time_cap', function (): void {
    $blocks = [
        plannedBlock(BlockType::Amrap, timeCap: 900),
    ];

    expect($this->estimator->estimate($blocks))->toBe(900);
});

it('estimates emom as rounds * work_interval', function (): void {
    // 10 rounds * 60 = 600
    $blocks = [
        plannedBlock(BlockType::Emom, rounds: 10, workInterval: 60),
    ];

    expect($this->estimator->estimate($blocks))->toBe(600);
});

it('estimates for_time as time_cap', function (): void {
    $blocks = [
        plannedBlock(BlockType::ForTime, timeCap: 1200, rounds: 3),
    ];

    expect($this->estimator->estimate($blocks))->toBe(1200);
});

it('estimates circuit with exercises and rest fields', function (): void {
    // 3 rounds, 2 exercises of 60s each, 15s rest between exercises, 30s rest between rounds
    // round_duration = 60 + 60 + 15 * (2-1) = 135
    // total = 3 * 135 + 30 * (3-1) = 405 + 60 = 465
    $blocks = [
        plannedBlock(
            BlockType::Circuit,
            exerciseDurations: [60, 60],
            rounds: 3,
            restBetweenExercises: 15,
            restBetweenRounds: 30,
        ),
    ];

    expect($this->estimator->estimate($blocks))->toBe(465);
});

it('estimates superset with exercises and rest_between_rounds', function (): void {
    // 4 rounds, 2 exercises of 45s each, 60s rest between rounds
    // total = 4 * (45+45) + 60 * (4-1) = 360 + 180 = 540
    $blocks = [
        plannedBlock(
            BlockType::Superset,
            exerciseDurations: [45, 45],
            rounds: 4,
            restBetweenRounds: 60,
        ),
    ];

    expect($this->estimator->estimate($blocks))->toBe(540);
});

it('estimates straight_sets by summing non-null durations', function (): void {
    // Strength (null) + cardio (300) + duration (120)
    $blocks = [
        plannedBlock(BlockType::StraightSets, exerciseDurations: [null, 300, 120]),
    ];

    expect($this->estimator->estimate($blocks))->toBe(420);
});

it('returns null for straight_sets with all null durations', function (): void {
    $blocks = [
        plannedBlock(BlockType::StraightSets, exerciseDurations: [null, null]),
    ];

    expect($this->estimator->estimate($blocks))->toBeNull();
});

it('estimates rest as sum of exercise durations', function (): void {
    $blocks = [
        plannedBlock(BlockType::Rest, exerciseDurations: [120]),
    ];

    expect($this->estimator->estimate($blocks))->toBe(120);
});

it('sums estimatable blocks across mixed types', function (): void {
    $blocks = [
        plannedBlock(BlockType::Amrap, timeCap: 600),
        plannedBlock(BlockType::Rest, exerciseDurations: [60]),
        plannedBlock(BlockType::Emom, rounds: 5, workInterval: 60),
    ];

    // 600 + 60 + 300 = 960
    expect($this->estimator->estimate($blocks))->toBe(960);
});

it('returns null when no blocks can be estimated', function (): void {
    $blocks = [
        plannedBlock(BlockType::StraightSets, exerciseDurations: [null]),
        plannedBlock(BlockType::Interval, rounds: null, workInterval: 30, restInterval: 10),
    ];

    expect($this->estimator->estimate($blocks))->toBeNull();
});

it('returns null for empty array', function (): void {
    expect($this->estimator->estimate([]))->toBeNull();
});

it('returns null for interval with missing fields', function (): void {
    $blocks = [
        plannedBlock(BlockType::Interval, rounds: 5, workInterval: null, restInterval: 10),
    ];

    expect($this->estimator->estimate($blocks))->toBeNull();
});

it('returns null for circuit without rounds', function (): void {
    $blocks = [
        plannedBlock(BlockType::Circuit, exerciseDurations: [60], rounds: null),
    ];

    expect($this->estimator->estimate($blocks))->toBeNull();
});

it('returns null for emom with missing fields', function (): void {
    $blocks = [
        plannedBlock(BlockType::Emom, rounds: null, workInterval: 60),
    ];

    expect($this->estimator->estimate($blocks))->toBeNull();
});

it('handles circuit with zero rest', function (): void {
    // 2 rounds, 2 exercises of 30s each, no rest
    // total = 2 * (30 + 30 + 0) + 0 = 120
    $blocks = [
        plannedBlock(
            BlockType::Circuit,
            exerciseDurations: [30, 30],
            rounds: 2,
            restBetweenExercises: 0,
            restBetweenRounds: 0,
        ),
    ];

    expect($this->estimator->estimate($blocks))->toBe(120);
});
