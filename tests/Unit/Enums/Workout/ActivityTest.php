<?php

use App\Enums\Workout\Activity;

test('activity enum has correct values for representative cases', function () {
    expect(Activity::Run->value)->toBe('run');
    expect(Activity::Strength->value)->toBe('strength');
    expect(Activity::Cardio->value)->toBe('cardio');
    expect(Activity::HIIT->value)->toBe('hiit');
    expect(Activity::TrailRun->value)->toBe('trail_run');
    expect(Activity::Bike->value)->toBe('bike');
    expect(Activity::PoolSwim->value)->toBe('pool_swim');
    expect(Activity::Yoga->value)->toBe('yoga');
});

test('activity enum returns correct labels for special cases', function () {
    expect(Activity::HIIT->label())->toBe('HIIT');
    expect(Activity::SUP->label())->toBe('SUP');
    expect(Activity::EBike->label())->toBe('E-Bike');
    expect(Activity::XCClassicSki->label())->toBe('XC Classic Ski');
    expect(Activity::XCSkateSki->label())->toBe('XC Skate Ski');
    expect(Activity::MixedMartialArts->label())->toBe('MMA');
});

test('activity enum returns category', function () {
    expect(Activity::Run->category())->toBe('running');
    expect(Activity::TrailRun->category())->toBe('running');
    expect(Activity::Bike->category())->toBe('cycling');
    expect(Activity::Strength->category())->toBe('gym');
    expect(Activity::PoolSwim->category())->toBe('swimming');
    expect(Activity::Yoga->category())->toBe('flexibility');
    expect(Activity::Soccer->category())->toBe('team');
    expect(Activity::Meditation->category())->toBe('mind_body');
    expect(Activity::Other->category())->toBe('other');
});

test('activity enum returns icon by category', function () {
    expect(Activity::Run->icon())->toBe('bolt');
    expect(Activity::Strength->icon())->toBe('fire');
    expect(Activity::Bike->icon())->toBe('arrow-path');
});

test('activity enum returns color by category', function () {
    expect(Activity::Run->color())->toBe('blue');
    expect(Activity::Strength->color())->toBe('orange');
    expect(Activity::Bike->color())->toBe('green');
});
