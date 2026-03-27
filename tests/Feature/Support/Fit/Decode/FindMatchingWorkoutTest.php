<?php

declare(strict_types=1);

use App\Actions\Garmin\FindMatchingWorkout;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedSession;
use App\Enums\Workout\Activity;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;

function makeParsedActivity(int $sport, int $subSport, CarbonImmutable $startTime): ParsedActivity
{
    return new ParsedActivity(
        session: new ParsedSession(
            sport: $sport,
            subSport: $subSport,
            startTime: $startTime,
            totalElapsedTime: 1800,
            totalDistance: null,
            totalCalories: null,
            avgHeartRate: null,
            maxHeartRate: null,
            avgPower: null,
            workoutName: null,
        ),
        laps: [],
        sets: [],
        exerciseTitles: [],
    );
}

it('finds workout scheduled on same day with same activity', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $scheduledAt = CarbonImmutable::parse('2026-03-22 09:00:00');

    $workout = Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'scheduled_at' => $scheduledAt,
        'completed_at' => null,
    ]);

    $parsed = makeParsedActivity(1, 0, CarbonImmutable::parse('2026-03-22 09:30:00'));

    $finder = new FindMatchingWorkout;
    $matches = $finder->execute($user, $parsed);

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->id)->toBe($workout->id);
});

it('does not return workouts scheduled on different day', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'scheduled_at' => CarbonImmutable::parse('2026-03-21 09:00:00'),
        'completed_at' => null,
    ]);

    $parsed = makeParsedActivity(1, 0, CarbonImmutable::parse('2026-03-22 09:30:00'));

    $finder = new FindMatchingWorkout;
    $matches = $finder->execute($user, $parsed);

    expect($matches)->toBeEmpty();
});

it('does not return already completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->completed()->create([
        'activity' => Activity::Run,
        'scheduled_at' => CarbonImmutable::parse('2026-03-22 09:00:00'),
    ]);

    $parsed = makeParsedActivity(1, 0, CarbonImmutable::parse('2026-03-22 09:30:00'));

    $finder = new FindMatchingWorkout;
    $matches = $finder->execute($user, $parsed);

    expect($matches)->toBeEmpty();
});

it('does not return workouts with different activity type', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Strength,
        'scheduled_at' => CarbonImmutable::parse('2026-03-22 09:00:00'),
        'completed_at' => null,
    ]);

    $parsed = makeParsedActivity(1, 0, CarbonImmutable::parse('2026-03-22 09:30:00'));

    $finder = new FindMatchingWorkout;
    $matches = $finder->execute($user, $parsed);

    expect($matches)->toBeEmpty();
});

it('orders matches by closest scheduled_at to activity start', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $farWorkout = Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'scheduled_at' => CarbonImmutable::parse('2026-03-22 06:00:00'),
        'completed_at' => null,
    ]);

    $closeWorkout = Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'scheduled_at' => CarbonImmutable::parse('2026-03-22 09:00:00'),
        'completed_at' => null,
    ]);

    $parsed = makeParsedActivity(1, 0, CarbonImmutable::parse('2026-03-22 09:30:00'));

    $finder = new FindMatchingWorkout;
    $matches = $finder->execute($user, $parsed);

    expect($matches)->toHaveCount(2)
        ->and($matches->first()->id)->toBe($closeWorkout->id)
        ->and($matches->last()->id)->toBe($farWorkout->id);
});

it('returns empty collection when no matches exist', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $parsed = makeParsedActivity(1, 0, CarbonImmutable::parse('2026-03-22 09:30:00'));

    $finder = new FindMatchingWorkout;
    $matches = $finder->execute($user, $parsed);

    expect($matches)->toBeEmpty();
});
