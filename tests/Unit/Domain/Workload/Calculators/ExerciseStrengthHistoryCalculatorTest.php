<?php

use App\Domain\Workload\Calculators\ExerciseStrengthHistoryCalculator;
use App\Domain\Workload\ValueObjects\StrengthRecord;

beforeEach(function (): void {
    $this->calculator = new ExerciseStrengthHistoryCalculator;
});

it('picks highest weight per date', function (): void {
    $records = [
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 80.0, 5, sets: 3),
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 95.0, 3, sets: 3),
    ];

    $result = $this->calculator->calculate($records, 1, 'Bench Press');

    expect($result->points)->toHaveCount(1);
    expect($result->points[0]->maxWeight)->toBe(95.0);
});

it('sums volume as sets x reps x weight per date', function (): void {
    $records = [
        // 3 sets x 5 reps x 80kg = 1200
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 80.0, 5, sets: 3),
        // 3 sets x 3 reps x 95kg = 855
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 95.0, 3, sets: 3),
    ];

    $result = $this->calculator->calculate($records, 1, 'Bench Press');

    expect($result->points[0]->volume)->toBe(1200.0 + 855.0);
});

it('picks highest Epley e1RM per date', function (): void {
    $records = [
        // 80 * (1 + 5/30) = 93.33
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 80.0, 5, sets: 3),
        // 95 * (1 + 3/30) = 104.5
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 95.0, 3, sets: 3),
    ];

    $result = $this->calculator->calculate($records, 1, 'Bench Press');

    expect($result->points[0]->estimated1RM)->toEqualWithDelta(104.5, 0.1);
});

it('sorts points chronologically', function (): void {
    $records = [
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-15'), 90.0, 5, sets: 3),
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-01'), 80.0, 5, sets: 3),
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 85.0, 5, sets: 3),
    ];

    $result = $this->calculator->calculate($records, 1, 'Bench Press');

    expect($result->points)->toHaveCount(3);
    expect($result->points[0]->date->format('Y-m-d'))->toBe('2026-02-01');
    expect($result->points[1]->date->format('Y-m-d'))->toBe('2026-02-10');
    expect($result->points[2]->date->format('Y-m-d'))->toBe('2026-02-15');
});

it('returns empty points for empty records', function (): void {
    $result = $this->calculator->calculate([], 1, 'Bench Press');

    expect($result->points)->toBeEmpty();
    expect($result->exerciseId)->toBe(1);
    expect($result->exerciseName)->toBe('Bench Press');
});

it('handles single record per date', function (): void {
    $records = [
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 100.0, 5, sets: 4),
    ];

    $result = $this->calculator->calculate($records, 1, 'Bench Press');

    expect($result->points)->toHaveCount(1);
    expect($result->points[0]->maxWeight)->toBe(100.0);
    expect($result->points[0]->volume)->toBe(4.0 * 5 * 100.0);
    expect($result->points[0]->estimated1RM)->toEqualWithDelta(116.67, 0.01);
});
