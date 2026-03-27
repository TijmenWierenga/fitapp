<?php

use App\Actions\Garmin\SportMapper;
use App\Enums\Workout\Activity;

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

    expect($mapping->sport)->toBe(10)
        ->and($mapping->subSport)->toBe(20);
});

it('maps HIIT and cardio to training with cardio sub sport', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);

    expect($mapping->sport)->toBe(10)
        ->and($mapping->subSport)->toBe(26);
})->with([
    'hiit' => Activity::HIIT,
    'cardio' => Activity::Cardio,
]);

it('maps yoga to training with yoga sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Yoga);

    expect($mapping->sport)->toBe(10)
        ->and($mapping->subSport)->toBe(43);
});

it('maps pilates to training with pilates sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Pilates);

    expect($mapping->sport)->toBe(10)
        ->and($mapping->subSport)->toBe(44);
});

it('maps mobility to training with flexibility sub sport', function () {
    $mapping = SportMapper::fromActivity(Activity::Mobility);

    expect($mapping->sport)->toBe(10)
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

// Reverse mapping: toActivity()

it('round-trips explicitly mapped activities', function (Activity $activity) {
    $mapping = SportMapper::fromActivity($activity);
    $result = SportMapper::toActivity($mapping->sport, $mapping->subSport);

    expect($result)->toBe($activity);
})->with([
    'strength' => Activity::Strength,
    'hiit' => Activity::HIIT,
    'yoga' => Activity::Yoga,
    'pilates' => Activity::Pilates,
    'mobility' => Activity::Mobility,
]);

it('maps running sub-sports correctly', function (int $subSport, Activity $expected) {
    expect(SportMapper::toActivity(1, $subSport))->toBe($expected);
})->with([
    'generic run' => [0, Activity::Run],
    'treadmill' => [1, Activity::Treadmill],
    'trail run' => [14, Activity::TrailRun],
    'ultra run' => [45, Activity::UltraRun],
]);

it('maps cycling sub-sports correctly', function (int $subSport, Activity $expected) {
    expect(SportMapper::toActivity(2, $subSport))->toBe($expected);
})->with([
    'generic bike' => [0, Activity::Bike],
    'bike indoor' => [6, Activity::BikeIndoor],
    'road bike' => [7, Activity::RoadBike],
    'mountain bike' => [8, Activity::MountainBike],
    'gravel bike' => [11, Activity::GravelBike],
    'e-bike' => [28, Activity::EBike],
]);

it('maps swimming sub-sports correctly', function (int $subSport, Activity $expected) {
    expect(SportMapper::toActivity(5, $subSport))->toBe($expected);
})->with([
    'generic swim' => [0, Activity::PoolSwim],
    'pool swim' => [17, Activity::PoolSwim],
    'open water' => [18, Activity::OpenWater],
]);

it('maps training sub-sports correctly', function (int $subSport, Activity $expected) {
    expect(SportMapper::toActivity(10, $subSport))->toBe($expected);
})->with([
    'strength' => [20, Activity::Strength],
    'hiit' => [26, Activity::HIIT],
    'yoga' => [43, Activity::Yoga],
    'pilates' => [44, Activity::Pilates],
    'mobility' => [19, Activity::Mobility],
    'generic training' => [0, Activity::Cardio],
]);

it('maps walk and hike sports', function () {
    expect(SportMapper::toActivity(11, 0))->toBe(Activity::Walk)
        ->and(SportMapper::toActivity(17, 0))->toBe(Activity::Hike);
});

it('falls back to Other for unknown sport', function () {
    expect(SportMapper::toActivity(999, 0))->toBe(Activity::Other);
});
