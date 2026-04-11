<?php

use App\Actions\Garmin\ParsedActivityHelper;
use App\DataTransferObjects\Fit\ParsedSet;

it('groups consecutive sets by exercise category and name', function () {
    $sets = collect([
        new ParsedSet(0, 1, 60, 10, 80.0, 0, 0),
        new ParsedSet(1, 1, 55, 10, 80.0, 0, 0),
        new ParsedSet(2, 1, 60, 12, 60.0, 28, 0),
        new ParsedSet(3, 1, 55, 12, 60.0, 28, 0),
    ]);

    $groups = ParsedActivityHelper::groupSetsByExercise($sets);

    expect($groups)->toHaveCount(2);
    expect($groups[0]->key)->toBe('0:0');
    expect($groups[0]->sets)->toHaveCount(2);
    expect($groups[1]->key)->toBe('28:0');
    expect($groups[1]->sets)->toHaveCount(2);
});

it('groups unidentified sets by weight', function () {
    $sets = collect([
        new ParsedSet(0, 1, 74, 15, 6.0, null, null),
        new ParsedSet(1, 1, 40, 15, 6.0, null, null),
        new ParsedSet(2, 1, 36, 12, 14.0, null, null),
        new ParsedSet(3, 1, 36, 12, 14.0, null, null),
    ]);

    $groups = ParsedActivityHelper::groupSetsByExercise($sets);

    expect($groups)->toHaveCount(2);
    expect($groups[0]->key)->toBe('weight:6');
    expect($groups[0]->sets)->toHaveCount(2);
    expect($groups[1]->key)->toBe('weight:14');
    expect($groups[1]->sets)->toHaveCount(2);
});

it('splits unidentified sets when weight changes then returns', function () {
    $sets = collect([
        new ParsedSet(0, 1, 60, 10, 6.0, null, null),
        new ParsedSet(1, 1, 60, 12, 0.0, null, null),
        new ParsedSet(2, 1, 60, 10, 6.0, null, null),
    ]);

    $groups = ParsedActivityHelper::groupSetsByExercise($sets);

    expect($groups)->toHaveCount(3);
});
