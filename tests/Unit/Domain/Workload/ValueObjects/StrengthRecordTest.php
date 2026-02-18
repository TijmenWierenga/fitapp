<?php

use App\Domain\Workload\ValueObjects\StrengthRecord;

it('calculates e1RM with Epley formula', function (float $weight, int $reps, float $expected): void {
    $record = new StrengthRecord(
        exerciseId: 1,
        exerciseName: 'Bench Press',
        performedAt: new DateTimeImmutable('2026-02-14'),
        weight: $weight,
        reps: $reps,
    );

    expect($record->estimated1RM())->toEqualWithDelta($expected, 0.01);
})->with([
    'moderate reps' => [100.0, 10, 133.33],  // 100 * (1 + 10/30)
    'high reps' => [60.0, 15, 90.0],         // 60 * (1 + 15/30)
    'low reps' => [120.0, 3, 132.0],         // 120 * (1 + 3/30)
    'five reps' => [80.0, 5, 93.33],         // 80 * (1 + 5/30)
]);

it('returns weight directly for single rep', function (): void {
    $record = new StrengthRecord(
        exerciseId: 1,
        exerciseName: 'Deadlift',
        performedAt: new DateTimeImmutable('2026-02-14'),
        weight: 200.0,
        reps: 1,
    );

    expect($record->estimated1RM())->toBe(200.0);
});

it('returns zero for zero weight', function (): void {
    $record = new StrengthRecord(
        exerciseId: 1,
        exerciseName: 'Bodyweight',
        performedAt: new DateTimeImmutable('2026-02-14'),
        weight: 0.0,
        reps: 10,
    );

    expect($record->estimated1RM())->toBe(0.0);
});
