<?php

use App\DataTransferObjects\Fit\ParsedSet;

it('marks active exercise sets as active', function () {
    $set = new ParsedSet(index: 0, setType: 1, duration: 60, repetitions: 10, weight: 80.0, exerciseCategory: 0, exerciseName: 0);

    expect($set->isActive())->toBeTrue();
});

it('marks rest sets as inactive', function () {
    $set = new ParsedSet(index: 0, setType: 0, duration: 60, repetitions: null, weight: null, exerciseCategory: null, exerciseName: null);

    expect($set->isActive())->toBeFalse();
});
