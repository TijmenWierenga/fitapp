<?php

use App\Domain\Workload\Calculators\StrengthProgressionCalculator;
use App\Domain\Workload\ValueObjects\DateRange;
use App\Domain\Workload\ValueObjects\StrengthRecord;

beforeEach(function (): void {
    $this->calculator = new StrengthProgressionCalculator;
    $this->currentPeriod = new DateRange(
        from: new DateTimeImmutable('2026-01-19'),
        to: new DateTimeImmutable('2026-02-15'),
    );
    $this->previousPeriod = new DateRange(
        from: new DateTimeImmutable('2025-12-22'),
        to: new DateTimeImmutable('2026-01-18'),
    );
});

it('calculates e1RM progression between periods', function (): void {
    $records = [
        // Current period: bench press 100kg x 5 → e1RM = 100*(1+5/30) = 116.67
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 100.0, 5),
        // Previous period: bench press 90kg x 5 → e1RM = 90*(1+5/30) = 105.0
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-01-05'), 90.0, 5),
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results)->toHaveCount(1);
    expect($results[0]->exerciseName)->toBe('Bench Press');
    expect($results[0]->currentE1RM)->toEqualWithDelta(116.7, 0.1);
    expect($results[0]->previousE1RM)->toEqualWithDelta(105.0, 0.1);
    expect($results[0]->changePct)->toEqualWithDelta(11.1, 0.1);
    expect($results[0]->currentMaxWeight)->toEqualWithDelta(100.0, 0.1);
    expect($results[0]->previousMaxWeight)->toEqualWithDelta(90.0, 0.1);
    // sets=1 (default), reps=5, weight=100 → volume = 1*5*100 = 500
    expect($results[0]->currentVolume)->toEqualWithDelta(500.0, 0.1);
});

it('uses best e1RM from each period', function (): void {
    $records = [
        // Current period: two bench sessions, second is better
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-05'), 80.0, 10),   // e1RM = 106.67
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-12'), 100.0, 5),   // e1RM = 116.67
        // Previous period
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-01-05'), 90.0, 5),    // e1RM = 105.0
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results[0]->currentE1RM)->toEqualWithDelta(116.7, 0.1);
    expect($results[0]->currentMaxWeight)->toEqualWithDelta(100.0, 0.1);
    expect($results[0]->previousMaxWeight)->toEqualWithDelta(90.0, 0.1);
    // volume = (1*10*80) + (1*5*100) = 800 + 500 = 1300
    expect($results[0]->currentVolume)->toEqualWithDelta(1300.0, 0.1);
});

it('returns null previous when no previous period data', function (): void {
    $records = [
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 100.0, 5),
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results)->toHaveCount(1);
    expect($results[0]->previousE1RM)->toBeNull();
    expect($results[0]->changePct)->toBeNull();
    expect($results[0]->currentMaxWeight)->toEqualWithDelta(100.0, 0.1);
    expect($results[0]->previousMaxWeight)->toBeNull();
    expect($results[0]->currentVolume)->toEqualWithDelta(500.0, 0.1);
});

it('excludes exercises only in previous period', function (): void {
    $records = [
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-01-05'), 90.0, 5),
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results)->toBeEmpty();
});

it('handles multiple exercises', function (): void {
    $records = [
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 100.0, 5),
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-01-05'), 90.0, 5),
        new StrengthRecord(2, 'Squat', new DateTimeImmutable('2026-02-10'), 140.0, 3),
        new StrengthRecord(2, 'Squat', new DateTimeImmutable('2026-01-05'), 130.0, 3),
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results)->toHaveCount(2);

    $bench = collect($results)->firstWhere('exerciseName', 'Bench Press');
    expect($bench->currentMaxWeight)->toEqualWithDelta(100.0, 0.1);
    expect($bench->currentVolume)->toEqualWithDelta(500.0, 0.1);

    $squat = collect($results)->firstWhere('exerciseName', 'Squat');
    expect($squat->currentMaxWeight)->toEqualWithDelta(140.0, 0.1);
    expect($squat->currentVolume)->toEqualWithDelta(420.0, 0.1);
});

it('calculates negative change percentage', function (): void {
    $records = [
        // Current: 80kg x 5 → e1RM = 93.33
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 80.0, 5),
        // Previous: 100kg x 5 → e1RM = 116.67
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-01-05'), 100.0, 5),
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results[0]->changePct)->toBeLessThan(0);
    expect($results[0]->currentMaxWeight)->toEqualWithDelta(80.0, 0.1);
    expect($results[0]->previousMaxWeight)->toEqualWithDelta(100.0, 0.1);
});

it('handles empty records', function (): void {
    $results = $this->calculator->calculate([], $this->currentPeriod, $this->previousPeriod);

    expect($results)->toBeEmpty();
});

it('calculates volume with explicit sets', function (): void {
    $records = [
        // Current period: 3 sets × 8 reps × 80kg = 1920
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-02-10'), 80.0, 8, sets: 3),
        // Previous period
        new StrengthRecord(1, 'Bench Press', new DateTimeImmutable('2026-01-05'), 75.0, 8, sets: 3),
    ];

    $results = $this->calculator->calculate($records, $this->currentPeriod, $this->previousPeriod);

    expect($results)->toHaveCount(1);
    expect($results[0]->currentMaxWeight)->toEqualWithDelta(80.0, 0.1);
    expect($results[0]->previousMaxWeight)->toEqualWithDelta(75.0, 0.1);
    // 3 * 8 * 80 = 1920
    expect($results[0]->currentVolume)->toEqualWithDelta(1920.0, 0.1);
});
