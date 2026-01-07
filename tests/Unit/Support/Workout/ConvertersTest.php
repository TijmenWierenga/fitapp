<?php

use App\Support\Workout\DistanceConverter;
use App\Support\Workout\PaceConverter;
use App\Support\Workout\TimeConverter;

test('time converter converts minutes and seconds to total seconds', function () {
    expect(TimeConverter::toSeconds(5, 0))->toBe(300);
    expect(TimeConverter::toSeconds(1, 10))->toBe(70);
});

test('time converter converts total seconds to minutes and seconds', function () {
    expect(TimeConverter::fromSeconds(300))->toBe(['minutes' => 5, 'seconds' => 0]);
    expect(TimeConverter::fromSeconds(70))->toBe(['minutes' => 1, 'seconds' => 10]);
});

test('distance converter converts km and tens of meters to total meters', function () {
    expect(DistanceConverter::toMeters(1, 0))->toBe(1000);
    expect(DistanceConverter::toMeters(4, 55))->toBe(4550);
    expect(DistanceConverter::toMeters(0, 1))->toBe(10);
});

test('distance converter converts total meters to km and tens of meters', function () {
    expect(DistanceConverter::fromMeters(1000))->toBe(['kilometers' => 1, 'tens_of_meters' => 0]);
    expect(DistanceConverter::fromMeters(4550))->toBe(['kilometers' => 4, 'tens_of_meters' => 55]);
    expect(DistanceConverter::fromMeters(10))->toBe(['kilometers' => 0, 'tens_of_meters' => 1]);
});

test('pace converter converts minutes and seconds per km to total seconds', function () {
    expect(PaceConverter::toSecondsPerKm(5, 0))->toBe(300);
    expect(PaceConverter::toSecondsPerKm(4, 30))->toBe(270);
});

test('pace converter converts total seconds per km to minutes and seconds', function () {
    expect(PaceConverter::fromSecondsPerKm(300))->toBe(['minutes' => 5, 'seconds' => 0]);
    expect(PaceConverter::fromSecondsPerKm(270))->toBe(['minutes' => 4, 'seconds' => 30]);
});
