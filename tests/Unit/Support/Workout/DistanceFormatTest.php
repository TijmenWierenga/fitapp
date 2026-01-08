<?php

use App\Support\Workout\DistanceConverter;

test('it formats distances correctly', function () {
    expect(DistanceConverter::format(1000))->toBe('1 km');
    expect(DistanceConverter::format(1500))->toBe('1.5 km');
    expect(DistanceConverter::format(500))->toBe('500 m');
    expect(DistanceConverter::format(1550))->toBe('1.55 km');
});
