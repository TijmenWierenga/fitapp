<?php

declare(strict_types=1);

use App\Enums\Fit\GarminExerciseCategory;
use App\Models\Exercise;
use App\Support\Fit\Decode\MatchGarminExercise;

it('matches an exercise by garmin category and name', function () {
    $exercise = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::BenchPress, 1)
        ->create();

    $matcher = new MatchGarminExercise;
    $result = $matcher->match(GarminExerciseCategory::BenchPress->value, 1);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($exercise->id);
});

it('returns null for unknown garmin category and name', function () {
    $matcher = new MatchGarminExercise;
    $result = $matcher->match(9999, 9999);

    expect($result)->toBeNull();
});

it('returns null when category matches but name does not', function () {
    Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::BenchPress, 1)
        ->create();

    $matcher = new MatchGarminExercise;
    $result = $matcher->match(GarminExerciseCategory::BenchPress->value, 999);

    expect($result)->toBeNull();
});
