<?php

use App\Enums\Workout\Activity;
use App\Support\Fit\SportMapper;

it('maps running activities to sport 1', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);

    expect($mapping->sport)->toBe(1)
        ->and($mapping->subSport)->toBe(0);
})->with([
    'run' => Activity::Run,
    'treadmill' => Activity::Treadmill,
    'trail run' => Activity::TrailRun,
    'track run' => Activity::TrackRun,
    'indoor track' => Activity::IndoorTrack,
    'ultra run' => Activity::UltraRun,
]);

it('maps cycling activities to sport 2', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);

    expect($mapping->sport)->toBe(2)
        ->and($mapping->subSport)->toBe(0);
})->with([
    'bike' => Activity::Bike,
    'road bike' => Activity::RoadBike,
    'mountain bike' => Activity::MountainBike,
    'bike indoor' => Activity::BikeIndoor,
]);

it('maps swimming activities to sport 5', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);

    expect($mapping->sport)->toBe(5)
        ->and($mapping->subSport)->toBe(0);
})->with([
    'pool swim' => Activity::PoolSwim,
    'open water' => Activity::OpenWater,
]);

it('maps strength to training with strength sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Strength);

    expect($mapping->sport)->toBe(4)
        ->and($mapping->subSport)->toBe(20);
});

it('maps HIIT and cardio to training with cardio sub sport', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);

    expect($mapping->sport)->toBe(4)
        ->and($mapping->subSport)->toBe(26);
})->with([
    'hiit' => Activity::HIIT,
    'cardio' => Activity::Cardio,
]);

it('maps yoga to training with yoga sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Yoga);

    expect($mapping->sport)->toBe(4)
        ->and($mapping->subSport)->toBe(43);
});

it('maps pilates to training with pilates sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Pilates);

    expect($mapping->sport)->toBe(4)
        ->and($mapping->subSport)->toBe(44);
});

it('maps mobility to training with flexibility sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Mobility);

    expect($mapping->sport)->toBe(4)
        ->and($mapping->subSport)->toBe(19);
});

it('maps other activities to generic sport', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);

    expect($mapping->sport)->toBe(0)
        ->and($mapping->subSport)->toBe(0);
})->with([
    'golf' => Activity::Golf,
    'soccer' => Activity::Soccer,
    'tennis' => Activity::Tennis,
]);
