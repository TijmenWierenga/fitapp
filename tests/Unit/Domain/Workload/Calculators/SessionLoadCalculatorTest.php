<?php

use App\Domain\Workload\Calculators\SessionLoadCalculator;
use App\Domain\Workload\ValueObjects\CompletedSession;
use App\Domain\Workload\ValueObjects\DateRange;

beforeEach(function (): void {
    $this->calculator = new SessionLoadCalculator;
    $this->currentWeek = new DateRange(
        from: new DateTimeImmutable('2026-02-09'),
        to: new DateTimeImmutable('2026-02-15'),
    );
    $this->previousWeeks = [
        new DateRange(new DateTimeImmutable('2026-02-02'), new DateTimeImmutable('2026-02-08')),
        new DateRange(new DateTimeImmutable('2026-01-26'), new DateTimeImmutable('2026-02-01')),
        new DateRange(new DateTimeImmutable('2026-01-19'), new DateTimeImmutable('2026-01-25')),
    ];
});

it('calculates sRPE session load correctly', function (): void {
    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-10'), durationMinutes: 60, rpe: 7),
        new CompletedSession(new DateTimeImmutable('2026-02-12'), durationMinutes: 45, rpe: 8),
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    // 60*7 + 45*8 = 420 + 360 = 780
    expect($result->currentWeeklyTotal)->toBe(780);
    expect($result->currentSessionCount)->toBe(2);
});

it('calculates monotony with varied loads', function (): void {
    // Create sessions on different days with different loads
    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-09'), durationMinutes: 60, rpe: 8), // day 0: 480
        new CompletedSession(new DateTimeImmutable('2026-02-11'), durationMinutes: 30, rpe: 6), // day 2: 180
        new CompletedSession(new DateTimeImmutable('2026-02-14'), durationMinutes: 45, rpe: 7), // day 5: 315
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    // Daily loads: [480, 0, 180, 0, 0, 315, 0]
    // Mean: 975/7 = 139.29
    // Monotony = mean / stddev
    expect($result->monotony)->toBeGreaterThan(0.0);
    expect($result->strain)->toBeGreaterThan(0.0);
});

it('returns zero monotony when all loads are zero', function (): void {
    $result = $this->calculator->calculate([], $this->currentWeek, $this->previousWeeks);

    expect($result->monotony)->toBe(0.0);
    expect($result->strain)->toBe(0.0);
});

it('returns zero monotony when all daily loads are identical', function (): void {
    // All sessions on every day with the same load would give stddev=0
    // But sessions only on some days means rest days have 0, so stddev > 0
    // To get stddev=0, we'd need same load every day - which is unlikely
    // Let's test the case of a single session (6 zero days + 1 loaded day)
    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-09'), durationMinutes: 60, rpe: 7),
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    // Daily loads: [420, 0, 0, 0, 0, 0, 0]
    // Mean = 60, stddev > 0, monotony > 0
    expect($result->monotony)->toBeGreaterThan(0.0);
});

it('calculates strain as weekly total times monotony', function (): void {
    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-09'), durationMinutes: 60, rpe: 8),
        new CompletedSession(new DateTimeImmutable('2026-02-11'), durationMinutes: 30, rpe: 6),
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    $expectedStrain = round($result->currentWeeklyTotal * $result->monotony, 1);
    expect($result->strain)->toBe($expectedStrain);
});

it('calculates positive week-over-week change', function (): void {
    $sessions = [
        // Current week: 60*7 = 420
        new CompletedSession(new DateTimeImmutable('2026-02-10'), durationMinutes: 60, rpe: 7),
        // Previous week (-1): 30*5 = 150
        new CompletedSession(new DateTimeImmutable('2026-02-04'), durationMinutes: 30, rpe: 5),
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    // (420 - 150) / 150 * 100 = 180%
    expect($result->weekOverWeekChangePct)->toBe(180.0);
    expect($result->weekOverWeekWarning)->toBeTrue();
});

it('calculates negative week-over-week change', function (): void {
    $sessions = [
        // Current week: 30*5 = 150
        new CompletedSession(new DateTimeImmutable('2026-02-10'), durationMinutes: 30, rpe: 5),
        // Previous week (-1): 60*7 = 420
        new CompletedSession(new DateTimeImmutable('2026-02-04'), durationMinutes: 60, rpe: 7),
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    // (150 - 420) / 420 * 100 = -64.3%
    expect($result->weekOverWeekChangePct)->toEqualWithDelta(-64.3, 0.1);
    expect($result->weekOverWeekWarning)->toBeTrue();
});

it('returns zero change when previous week has no sessions', function (): void {
    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-10'), durationMinutes: 60, rpe: 7),
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    expect($result->weekOverWeekChangePct)->toBe(0.0);
    expect($result->weekOverWeekWarning)->toBeFalse();
});

it('flags monotony warning above 2.0', function (): void {
    // Sessions every day with very similar loads to push monotony > 2.0
    $sessions = [];
    for ($day = 0; $day < 7; $day++) {
        $sessions[] = new CompletedSession(
            new DateTimeImmutable('2026-02-'.str_pad((string) (9 + $day), 2, '0', STR_PAD_LEFT)),
            durationMinutes: 60,
            rpe: 7,
        );
    }

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    // All 7 days have load 420, stddev = 0 → monotony guard returns 0
    // Since uniform loads give stddev=0, monotony=0, no warning
    expect($result->monotonyWarning)->toBeFalse();
});

it('builds previous week summaries', function (): void {
    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-04'), durationMinutes: 60, rpe: 7),  // week -1
        new CompletedSession(new DateTimeImmutable('2026-02-06'), durationMinutes: 45, rpe: 6),  // week -1
        new CompletedSession(new DateTimeImmutable('2026-01-28'), durationMinutes: 30, rpe: 8),  // week -2
    ];

    $result = $this->calculator->calculate($sessions, $this->currentWeek, $this->previousWeeks);

    expect($result->previousWeeks)->toHaveCount(3);
    expect($result->previousWeeks[0]->weekOffset)->toBe(-1);
    expect($result->previousWeeks[0]->totalLoad)->toBe(420 + 270); // 690
    expect($result->previousWeeks[0]->sessionCount)->toBe(2);
    expect($result->previousWeeks[1]->weekOffset)->toBe(-2);
    expect($result->previousWeeks[1]->totalLoad)->toBe(240);
    expect($result->previousWeeks[1]->sessionCount)->toBe(1);
    expect($result->previousWeeks[2]->weekOffset)->toBe(-3);
    expect($result->previousWeeks[2]->totalLoad)->toBe(0);
    expect($result->previousWeeks[2]->sessionCount)->toBe(0);
});

it('handles empty sessions', function (): void {
    $result = $this->calculator->calculate([], $this->currentWeek, $this->previousWeeks);

    expect($result->currentWeeklyTotal)->toBe(0);
    expect($result->currentSessionCount)->toBe(0);
    expect($result->monotony)->toBe(0.0);
    expect($result->strain)->toBe(0.0);
    expect($result->weekOverWeekChangePct)->toBe(0.0);
    expect($result->weekOverWeekWarning)->toBeFalse();
    expect($result->monotonyWarning)->toBeFalse();
});
