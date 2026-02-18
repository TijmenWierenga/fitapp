<?php

use App\Domain\Workload\Calculators\MuscleGroupVolumeCalculator;
use App\Domain\Workload\Enums\Trend;
use App\Domain\Workload\ValueObjects\DateRange;
use App\Domain\Workload\ValueObjects\MuscleGroupMapping;
use App\Domain\Workload\ValueObjects\PerformedExercise;

beforeEach(function (): void {
    $this->calculator = new MuscleGroupVolumeCalculator;
    $this->weekRanges = [
        new DateRange(new DateTimeImmutable('2026-02-09'), new DateTimeImmutable('2026-02-15')), // current
        new DateRange(new DateTimeImmutable('2026-02-02'), new DateTimeImmutable('2026-02-08')), // -1
        new DateRange(new DateTimeImmutable('2026-01-26'), new DateTimeImmutable('2026-02-01')), // -2
        new DateRange(new DateTimeImmutable('2026-01-19'), new DateTimeImmutable('2026-01-25')), // -3
    ];
    $this->chestMapping = new MuscleGroupMapping(1, 'chest', 'Chest', 'chest', 1.0);
    $this->tricepsMapping = new MuscleGroupMapping(2, 'triceps', 'Triceps', 'triceps', 0.5);
});

it('counts sets for current week', function (): void {
    $exercises = [
        new PerformedExercise(
            completedAt: new DateTimeImmutable('2026-02-10'),
            sets: 4,
            exerciseType: 'strength',
            muscleGroups: [$this->chestMapping],
        ),
        new PerformedExercise(
            completedAt: new DateTimeImmutable('2026-02-12'),
            sets: 3,
            exerciseType: 'strength',
            muscleGroups: [$this->chestMapping],
        ),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    expect($results)->toHaveCount(1);
    expect($results[0]->currentWeekSets)->toBe(7.0);
});

it('distributes sets via load factor', function (): void {
    $exercises = [
        new PerformedExercise(
            completedAt: new DateTimeImmutable('2026-02-10'),
            sets: 4,
            exerciseType: 'strength',
            muscleGroups: [$this->chestMapping, $this->tricepsMapping],
        ),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    $chest = collect($results)->firstWhere('muscleGroupId', 1);
    $triceps = collect($results)->firstWhere('muscleGroupId', 2);

    expect($chest->currentWeekSets)->toBe(4.0);  // 4 * 1.0
    expect($triceps->currentWeekSets)->toBe(2.0); // 4 * 0.5
});

it('calculates four-week average', function (): void {
    $exercises = [
        // Current week: 4 sets
        new PerformedExercise(new DateTimeImmutable('2026-02-10'), 4, 'strength', [$this->chestMapping]),
        // Week -1: 6 sets
        new PerformedExercise(new DateTimeImmutable('2026-02-04'), 6, 'strength', [$this->chestMapping]),
        // Week -2: 2 sets
        new PerformedExercise(new DateTimeImmutable('2026-01-28'), 2, 'strength', [$this->chestMapping]),
        // Week -3: 8 sets
        new PerformedExercise(new DateTimeImmutable('2026-01-20'), 8, 'strength', [$this->chestMapping]),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    // Average: (4 + 6 + 2 + 8) / 4 = 5.0
    expect($results[0]->fourWeekAverageSets)->toBe(5.0);
});

it('detects increasing trend', function (): void {
    $exercises = [
        // Current week: 10 sets (well above avg)
        new PerformedExercise(new DateTimeImmutable('2026-02-10'), 10, 'strength', [$this->chestMapping]),
        // Previous weeks: 4 sets each
        new PerformedExercise(new DateTimeImmutable('2026-02-04'), 4, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-01-28'), 4, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-01-20'), 4, 'strength', [$this->chestMapping]),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    // Avg: (10+4+4+4)/4 = 5.5, 10 > 5.5*1.1 = 6.05 → increasing
    expect($results[0]->trend)->toBe(Trend::Increasing);
});

it('detects stable trend', function (): void {
    $exercises = [
        new PerformedExercise(new DateTimeImmutable('2026-02-10'), 5, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-02-04'), 5, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-01-28'), 5, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-01-20'), 5, 'strength', [$this->chestMapping]),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    // Avg: 5.0, 5.0 is between 4.5 and 5.5 → stable
    expect($results[0]->trend)->toBe(Trend::Stable);
});

it('detects decreasing trend', function (): void {
    $exercises = [
        // Current week: 2 sets (well below avg)
        new PerformedExercise(new DateTimeImmutable('2026-02-10'), 2, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-02-04'), 8, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-01-28'), 8, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-01-20'), 8, 'strength', [$this->chestMapping]),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    // Avg: (2+8+8+8)/4 = 6.5, 2 < 6.5*0.9 = 5.85 → decreasing
    expect($results[0]->trend)->toBe(Trend::Decreasing);
});

it('only counts strength exercise type', function (): void {
    $exercises = [
        new PerformedExercise(new DateTimeImmutable('2026-02-10'), 4, 'strength', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-02-11'), 3, 'cardio', [$this->chestMapping]),
        new PerformedExercise(new DateTimeImmutable('2026-02-12'), 2, 'duration', [$this->chestMapping]),
    ];

    $results = $this->calculator->calculate($exercises, $this->weekRanges);

    expect($results)->toHaveCount(1);
    expect($results[0]->currentWeekSets)->toBe(4.0);
});

it('returns empty for no exercises', function (): void {
    $results = $this->calculator->calculate([], $this->weekRanges);

    expect($results)->toBeEmpty();
});

it('returns empty for no week ranges', function (): void {
    $exercises = [
        new PerformedExercise(new DateTimeImmutable('2026-02-10'), 4, 'strength', [$this->chestMapping]),
    ];

    $results = $this->calculator->calculate($exercises, []);

    expect($results)->toBeEmpty();
});
