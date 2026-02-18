<?php

use App\Domain\Workload\ValueObjects\DateRange;

it('contains dates within range', function (): void {
    $range = new DateRange(
        from: new DateTimeImmutable('2026-02-10'),
        to: new DateTimeImmutable('2026-02-16'),
    );

    expect($range->contains(new DateTimeImmutable('2026-02-13')))->toBeTrue();
});

it('contains boundary dates', function (): void {
    $range = new DateRange(
        from: new DateTimeImmutable('2026-02-10'),
        to: new DateTimeImmutable('2026-02-16'),
    );

    expect($range->contains(new DateTimeImmutable('2026-02-10')))->toBeTrue();
    expect($range->contains(new DateTimeImmutable('2026-02-16')))->toBeTrue();
});

it('excludes dates outside range', function (): void {
    $range = new DateRange(
        from: new DateTimeImmutable('2026-02-10'),
        to: new DateTimeImmutable('2026-02-16'),
    );

    expect($range->contains(new DateTimeImmutable('2026-02-09')))->toBeFalse();
    expect($range->contains(new DateTimeImmutable('2026-02-17')))->toBeFalse();
});

it('calculates days between from and to', function (): void {
    $range = new DateRange(
        from: new DateTimeImmutable('2026-02-10'),
        to: new DateTimeImmutable('2026-02-17'),
    );

    expect($range->days())->toBe(7);
});

it('returns zero days for same date', function (): void {
    $range = new DateRange(
        from: new DateTimeImmutable('2026-02-10'),
        to: new DateTimeImmutable('2026-02-10'),
    );

    expect($range->days())->toBe(0);
});
