<?php

use App\Enums\WorkloadZone;

describe('fromAcwr', function (): void {
    it('returns Inactive for zero ACWR', function (): void {
        expect(WorkloadZone::fromAcwr(0.0))->toBe(WorkloadZone::Inactive);
    });

    it('returns Undertraining below 0.8', function (float $acwr): void {
        expect(WorkloadZone::fromAcwr($acwr))->toBe(WorkloadZone::Undertraining);
    })->with([0.01, 0.5, 0.79]);

    it('returns SweetSpot between 0.8 and 1.3', function (float $acwr): void {
        expect(WorkloadZone::fromAcwr($acwr))->toBe(WorkloadZone::SweetSpot);
    })->with([0.8, 1.0, 1.3]);

    it('returns Caution between 1.3 exclusive and 1.5', function (float $acwr): void {
        expect(WorkloadZone::fromAcwr($acwr))->toBe(WorkloadZone::Caution);
    })->with([1.31, 1.4, 1.5]);

    it('returns Danger above 1.5', function (float $acwr): void {
        expect(WorkloadZone::fromAcwr($acwr))->toBe(WorkloadZone::Danger);
    })->with([1.51, 2.0, 3.0]);
});

describe('color', function (): void {
    it('maps zones to colors', function (WorkloadZone $zone, string $expected): void {
        expect($zone->color())->toBe($expected);
    })->with([
        [WorkloadZone::Inactive, 'gray'],
        [WorkloadZone::Undertraining, 'gray'],
        [WorkloadZone::SweetSpot, 'green'],
        [WorkloadZone::Caution, 'yellow'],
        [WorkloadZone::Danger, 'red'],
    ]);
});

describe('label', function (): void {
    it('maps zones to labels', function (WorkloadZone $zone, string $expected): void {
        expect($zone->label())->toBe($expected);
    })->with([
        [WorkloadZone::Inactive, 'Inactive'],
        [WorkloadZone::Undertraining, 'Undertraining'],
        [WorkloadZone::SweetSpot, 'Sweet Spot'],
        [WorkloadZone::Caution, 'Caution'],
        [WorkloadZone::Danger, 'Danger'],
    ]);
});
