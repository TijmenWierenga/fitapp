<?php

use App\Enums\Workout\Sport;

test('sport enum has correct values', function () {
    expect(Sport::Running->value)->toBe('running');
    expect(Sport::Strength->value)->toBe('strength');
    expect(Sport::Cardio->value)->toBe('cardio');
    expect(Sport::Hiit->value)->toBe('hiit');
});

test('sport enum returns correct labels', function () {
    expect(Sport::Running->label())->toBe('Running');
    expect(Sport::Strength->label())->toBe('Strength Training');
    expect(Sport::Cardio->label())->toBe('Cardio');
    expect(Sport::Hiit->label())->toBe('HIIT');
});

test('sport enum returns correct icons', function () {
    expect(Sport::Running->icon())->toBe('bolt');
    expect(Sport::Strength->icon())->toBe('fire');
    expect(Sport::Cardio->icon())->toBe('heart');
    expect(Sport::Hiit->icon())->toBe('bolt-slash');
});

test('sport enum returns correct colors', function () {
    expect(Sport::Running->color())->toBe('blue');
    expect(Sport::Strength->color())->toBe('orange');
    expect(Sport::Cardio->color())->toBe('red');
    expect(Sport::Hiit->color())->toBe('purple');
});

test('only running has step builder', function () {
    expect(Sport::Running->hasStepBuilder())->toBeTrue();
    expect(Sport::Strength->hasStepBuilder())->toBeFalse();
    expect(Sport::Cardio->hasStepBuilder())->toBeFalse();
    expect(Sport::Hiit->hasStepBuilder())->toBeFalse();
});
