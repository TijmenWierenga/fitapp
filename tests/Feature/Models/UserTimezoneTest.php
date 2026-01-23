<?php

use App\Models\User;
use Illuminate\Support\Carbon;

it('returns UTC timezone object when no timezone is set', function () {
    $user = User::factory()->create(['timezone' => null]);

    $timezoneObject = $user->getTimezoneObject();

    expect($timezoneObject)
        ->toBeInstanceOf(\DateTimeZone::class)
        ->and($timezoneObject->getName())->toBe('UTC');
});

it('returns the correct timezone object when timezone is set', function () {
    $user = User::factory()->withTimezone('America/New_York')->create();

    $timezoneObject = $user->getTimezoneObject();

    expect($timezoneObject)
        ->toBeInstanceOf(\DateTimeZone::class)
        ->and($timezoneObject->getName())->toBe('America/New_York');
});

it('converts a date to the user timezone', function () {
    $user = User::factory()->withTimezone('America/New_York')->create();

    // Create a UTC date
    $utcDate = Carbon::create(2026, 1, 15, 12, 0, 0, 'UTC');

    $userDate = $user->toUserTimezone($utcDate);

    // America/New_York is UTC-5 in January (EST)
    expect($userDate->timezone->getName())->toBe('America/New_York')
        ->and($userDate->hour)->toBe(7);
});

it('does not mutate the original date when converting to user timezone', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();

    $originalDate = Carbon::create(2026, 1, 15, 12, 0, 0, 'UTC');
    $originalHour = $originalDate->hour;

    $user->toUserTimezone($originalDate);

    expect($originalDate->hour)->toBe($originalHour)
        ->and($originalDate->timezone->getName())->toBe('UTC');
});

it('returns current time in the user timezone', function () {
    $user = User::factory()->withTimezone('Pacific/Auckland')->create();

    $userTime = $user->currentTimeInTimezone();

    expect($userTime->timezone->getName())->toBe('Pacific/Auckland');
});

it('defaults to UTC for current time when no timezone is set', function () {
    $user = User::factory()->create(['timezone' => null]);

    $userTime = $user->currentTimeInTimezone();

    expect($userTime->timezone->getName())->toBe('UTC');
});

it('can be created with a timezone using the factory', function () {
    $user = User::factory()->withTimezone('Europe/London')->create();

    expect($user->timezone)->toBe('Europe/London');
});

it('stores timezone as a fillable attribute', function () {
    $user = User::factory()->create();

    $user->fill(['timezone' => 'Asia/Tokyo']);
    $user->save();

    expect($user->fresh()->timezone)->toBe('Asia/Tokyo');
});
