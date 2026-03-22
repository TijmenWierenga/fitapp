<?php

use App\Domain\Workload\Calculators\EwmaLoadCalculator;
use App\Domain\Workload\Enums\AcwrZone;
use App\Domain\Workload\ValueObjects\CompletedSession;

beforeEach(function (): void {
    $this->calculator = new EwmaLoadCalculator;
});

it('returns zeros and null ACWR when no sessions exist', function (): void {
    $from = new DateTimeImmutable('2026-01-01');
    $to = new DateTimeImmutable('2026-02-11');

    $result = $this->calculator->calculate([], $from, $to);

    expect($result->currentAcuteLoad)->toBe(0.0);
    expect($result->currentChronicLoad)->toBe(0.0);
    expect($result->acwr)->toBeNull();
    expect($result->tsb)->toBe(0.0);
    expect($result->acwrZone)->toBe(AcwrZone::Undertraining);
    expect($result->totalSessions)->toBe(0);
});

it('calculates correct EWMA values for a single session', function (): void {
    $from = new DateTimeImmutable('2026-02-01');
    $to = new DateTimeImmutable('2026-02-01');

    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-01'), durationMinutes: 60, rpe: 7),
    ];

    $result = $this->calculator->calculate($sessions, $from, $to);

    // Day load = 60 * 7 = 420
    // Acute: 420 * 0.25 + 0 * 0.75 = 105.0
    // Chronic: 420 * (2/29) + 0 * (27/29) = 420 * 0.0689655... = 28.965...
    expect($result->currentAcuteLoad)->toEqualWithDelta(105.0, 0.1);
    expect($result->currentChronicLoad)->toEqualWithDelta(29.0, 0.1);
    expect($result->acwr)->toBeGreaterThan(0);
    expect($result->totalSessions)->toBe(1);
});

it('converges toward daily load with steady training', function (): void {
    $from = new DateTimeImmutable('2025-12-01');
    $to = new DateTimeImmutable('2026-02-14');

    // Train every day for ~75 days with load 300 (50min * RPE 6)
    $sessions = [];
    $current = $from;
    while ($current <= $to) {
        $sessions[] = new CompletedSession($current, durationMinutes: 50, rpe: 6);
        $current = $current->modify('+1 day');
    }

    $result = $this->calculator->calculate($sessions, $from, $to);

    // After many days of steady 300 load, both EWMA values should converge toward 300
    expect($result->currentAcuteLoad)->toEqualWithDelta(300.0, 5.0);
    expect($result->currentChronicLoad)->toEqualWithDelta(300.0, 5.0);
    // ACWR should be approximately 1.0
    expect($result->acwr)->toEqualWithDelta(1.0, 0.05);
    // TSB should be near 0
    expect($result->tsb)->toEqualWithDelta(0.0, 5.0);
    expect($result->acwrZone)->toBe(AcwrZone::SweetSpot);
});

it('shows acute rising faster than chronic after a spike', function (): void {
    $from = new DateTimeImmutable('2025-12-15');
    $to = new DateTimeImmutable('2026-02-14');

    // 8 weeks of moderate training (load 200), then spike to 600 for last 5 days
    $sessions = [];
    $current = $from;
    $spikeStart = new DateTimeImmutable('2026-02-10');

    while ($current <= $to) {
        $load = $current >= $spikeStart ? 600 : 200;
        $durationMinutes = $load >= 600 ? 75 : 50;
        $rpe = $load >= 600 ? 8 : 4;

        $sessions[] = new CompletedSession($current, durationMinutes: $durationMinutes, rpe: $rpe);
        $current = $current->modify('+1 day');
    }

    $result = $this->calculator->calculate($sessions, $from, $to);

    // Acute should be higher than chronic after the spike
    expect($result->currentAcuteLoad)->toBeGreaterThan($result->currentChronicLoad);
    // ACWR should be > 1.0 (acute > chronic)
    expect($result->acwr)->toBeGreaterThan(1.0);
    // TSB should be negative (fatigued)
    expect($result->tsb)->toBeLessThan(0);
});

it('classifies ACWR zone boundaries correctly', function (): void {
    expect(AcwrZone::fromAcwr(null))->toBe(AcwrZone::Undertraining);
    expect(AcwrZone::fromAcwr(0.0))->toBe(AcwrZone::Undertraining);
    expect(AcwrZone::fromAcwr(0.79))->toBe(AcwrZone::Undertraining);
    expect(AcwrZone::fromAcwr(0.8))->toBe(AcwrZone::SweetSpot);
    expect(AcwrZone::fromAcwr(1.0))->toBe(AcwrZone::SweetSpot);
    expect(AcwrZone::fromAcwr(1.3))->toBe(AcwrZone::SweetSpot);
    expect(AcwrZone::fromAcwr(1.31))->toBe(AcwrZone::Caution);
    expect(AcwrZone::fromAcwr(1.5))->toBe(AcwrZone::Caution);
    expect(AcwrZone::fromAcwr(1.51))->toBe(AcwrZone::Danger);
    expect(AcwrZone::fromAcwr(2.0))->toBe(AcwrZone::Danger);
});

it('calculates positive TSB when fresh (chronic > acute)', function (): void {
    $from = new DateTimeImmutable('2025-12-15');
    $to = new DateTimeImmutable('2026-02-14');

    // Heavy training for weeks, then rest for last 7 days
    $sessions = [];
    $current = $from;
    $restStart = new DateTimeImmutable('2026-02-08');

    while ($current <= $to) {
        if ($current < $restStart) {
            $sessions[] = new CompletedSession($current, durationMinutes: 60, rpe: 7);
        }
        $current = $current->modify('+1 day');
    }

    $result = $this->calculator->calculate($sessions, $from, $to);

    // After rest, acute decays faster than chronic → TSB positive (fresh)
    expect($result->tsb)->toBeGreaterThan(0);
    expect($result->currentChronicLoad)->toBeGreaterThan($result->currentAcuteLoad);
});

it('returns correct number of chart data points', function (): void {
    $from = new DateTimeImmutable('2025-12-15');
    $to = new DateTimeImmutable('2026-02-14');

    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-01-15'), durationMinutes: 60, rpe: 7),
    ];

    $result = $this->calculator->calculate($sessions, $from, $to);

    // Window is 62 days total, chart shows last 43 points (42 days + 1 inclusive)
    expect(count($result->dailyPoints))->toBe(43);
});

it('returns all days as chart points when window is shorter than 42 days', function (): void {
    $from = new DateTimeImmutable('2026-02-01');
    $to = new DateTimeImmutable('2026-02-14');

    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-05'), durationMinutes: 60, rpe: 7),
    ];

    $result = $this->calculator->calculate($sessions, $from, $to);

    // 14-day window → all 14 days (from day 0 to day 13) should be chart points
    expect(count($result->dailyPoints))->toBe(14);
});

it('aggregates multiple sessions on the same day', function (): void {
    $from = new DateTimeImmutable('2026-02-01');
    $to = new DateTimeImmutable('2026-02-01');

    $sessions = [
        new CompletedSession(new DateTimeImmutable('2026-02-01'), durationMinutes: 30, rpe: 6),
        new CompletedSession(new DateTimeImmutable('2026-02-01'), durationMinutes: 45, rpe: 8),
    ];

    $result = $this->calculator->calculate($sessions, $from, $to);

    // Combined daily load: (30*6) + (45*8) = 180 + 360 = 540
    // Acute: 540 * 0.25 = 135
    expect($result->currentAcuteLoad)->toEqualWithDelta(135.0, 0.1);
    expect($result->totalSessions)->toBe(2);
});
