<?php

use App\Support\Workout\TimeConverter;

test('time converter handles hours in format', function () {
    expect(TimeConverter::format(3600))->toBe('1h');
    expect(TimeConverter::format(3661))->toBe('1h 1min 1s');
    expect(TimeConverter::format(60))->toBe('1min');
    expect(TimeConverter::format(3599))->toBe('59min 59s');
});

test('time converter converts total seconds to hours, minutes and seconds', function () {
    expect(TimeConverter::fromSeconds(3661))->toBe(['hours' => 1, 'minutes' => 1, 'seconds' => 1]);
    expect(TimeConverter::fromSeconds(60))->toBe(['hours' => 0, 'minutes' => 1, 'seconds' => 0]);
});
