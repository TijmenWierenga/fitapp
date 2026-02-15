<?php

use App\Enums\Fit\GarminExerciseCategory;

it('has the expected number of cases', function () {
    expect(GarminExerciseCategory::cases())->toHaveCount(34); // 33 categories + Unknown
});

it('maps correct values for key categories', function (GarminExerciseCategory $category, int $expectedValue) {
    expect($category->value)->toBe($expectedValue);
})->with([
    'bench press' => [GarminExerciseCategory::BenchPress, 0],
    'squat' => [GarminExerciseCategory::Squat, 28],
    'run' => [GarminExerciseCategory::Run, 32],
    'unknown' => [GarminExerciseCategory::Unknown, 65534],
]);

it('has a label for every case', function () {
    foreach (GarminExerciseCategory::cases() as $category) {
        expect($category->label())->toBeString()->not->toBeEmpty();
    }
});

it('can be constructed from value', function () {
    expect(GarminExerciseCategory::from(0))->toBe(GarminExerciseCategory::BenchPress)
        ->and(GarminExerciseCategory::from(28))->toBe(GarminExerciseCategory::Squat);
});
