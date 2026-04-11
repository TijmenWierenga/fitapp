<?php

declare(strict_types=1);

use App\Exceptions\FitParseException;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Fit\Decode\FitDecoder;
use Tests\Support\FitActivityFixtureBuilder;

it('parses a synthetic strength activity', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, totalCalories: 400)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0, exerciseCategory: 4, exerciseName: 0, duration: 45)
        ->addSet(setType: 0, duration: 90) // rest
        ->addSet(setType: 1, repetitions: 10, weight: 80.0, exerciseCategory: 4, exerciseName: 0, duration: 48)
        ->addSet(setType: 0, duration: 90) // rest
        ->addSet(setType: 1, repetitions: 8, weight: 85.0, exerciseCategory: 4, exerciseName: 0, duration: 42)
        ->addExerciseTitle(exerciseCategory: 4, exerciseName: 0, displayName: 'Bench Press')
        ->build();

    $parser = new FitActivityParser(new FitDecoder);
    $activity = $parser->parse($data);

    expect($activity->session->sport)->toBe(10)
        ->and($activity->session->subSport)->toBe(20)
        ->and($activity->session->totalElapsedTime)->toBe(3600)
        ->and($activity->session->totalCalories)->toBe(400);

    // Filter active sets only
    $activeSets = collect($activity->sets)->filter->isActive();
    expect($activeSets)->toHaveCount(3);

    $firstSet = $activeSets->first();
    expect($firstSet->repetitions)->toBe(10)
        ->and($firstSet->weight)->toBe(80.0)
        ->and($firstSet->exerciseCategory)->toBe(4)
        ->and($firstSet->exerciseName)->toBe(0)
        ->and($firstSet->duration)->toBe(45);

    $thirdSet = $activeSets->last();
    expect($thirdSet->repetitions)->toBe(8)
        ->and($thirdSet->weight)->toBe(85.0);

    expect($activity->exerciseTitles)->toHaveCount(1);
    expect($activity->exerciseTitles[0]->displayName)->toBe('Bench Press')
        ->and($activity->exerciseTitles[0]->exerciseCategory)->toBe(4);
});

it('parses a synthetic cardio activity', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000, avgHeartRate: 155, maxHeartRate: 178)
        ->addLap(totalElapsedTime: 360, totalDistance: 1000, avgHeartRate: 150, maxHeartRate: 165, avgCadence: 85)
        ->addLap(totalElapsedTime: 350, totalDistance: 1000, avgHeartRate: 155, maxHeartRate: 170, avgCadence: 87)
        ->addLap(totalElapsedTime: 340, totalDistance: 1000, avgHeartRate: 158, maxHeartRate: 175, avgCadence: 86)
        ->build();

    $parser = new FitActivityParser(new FitDecoder);
    $activity = $parser->parse($data);

    expect($activity->session->sport)->toBe(1)
        ->and($activity->session->subSport)->toBe(0)
        ->and($activity->session->totalElapsedTime)->toBe(1800)
        ->and($activity->session->totalDistance)->toBe(5000.0)
        ->and($activity->session->avgHeartRate)->toBe(155)
        ->and($activity->session->maxHeartRate)->toBe(178);

    expect($activity->laps)->toHaveCount(3);

    $firstLap = $activity->laps[0];
    expect($firstLap->index)->toBe(0)
        ->and($firstLap->totalElapsedTime)->toBe(360)
        ->and($firstLap->totalDistance)->toBe(1000.0)
        ->and($firstLap->avgHeartRate)->toBe(150)
        ->and($firstLap->maxHeartRate)->toBe(165)
        ->and($firstLap->avgCadence)->toBe(85);

    $secondLap = $activity->laps[1];
    expect($secondLap->totalElapsedTime)->toBe(350)
        ->and($secondLap->totalDistance)->toBe(1000.0);
});

it('parses the SDK sample activity fixture', function () {
    $data = file_get_contents(dirname(__DIR__, 4).'/fixtures/fit/Activity.fit');

    $parser = new FitActivityParser(new FitDecoder);
    $activity = $parser->parse($data);

    expect($activity->session->sport)->toBe(1)
        ->and($activity->laps)->toHaveCount(5);
});

it('throws for non-activity file', function () {
    // Build a FIT file with file_id type = 5 (workout, not activity)
    $encoder = new App\Support\Fit\FitEncoder;
    $messages = [
        App\Support\Fit\FitMessageFactory::fileId(), // type = 5 (workout)
    ];
    $data = $encoder->encode($messages);

    $parser = new FitActivityParser(new FitDecoder);
    $parser->parse($data);
})->throws(FitParseException::class, 'not an activity');

it('parses session workout name when present', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder
        ->withSession(sport: 1, subSport: 0, workoutName: 'Morning Run')
        ->build();

    $parser = new FitActivityParser(new FitDecoder);
    $activity = $parser->parse($data);

    expect($activity->session->workoutName)->toBe('Morning Run');
});
