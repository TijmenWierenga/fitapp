<?php

use App\Mcp\Resources\WorkoutScheduleResource;
use App\Models\User;
use App\Models\Workout;
use Laravel\Mcp\Request;

beforeEach(function () {
    $this->resource = new WorkoutScheduleResource;
});

it('returns upcoming and completed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->count(3)->create();
    Workout::factory()->for($user)->completed()->count(2)->create();

    $request = makeResourceRequest(['userId' => (string) $user->id]);

    $response = $this->resource->handle($request);

    $text = (string) $response->content();

    expect($text)->toContain('Upcoming Workouts')
        ->and($text)->toContain('Recently Completed Workouts');
});

it('uses default limits of 20 upcoming and 10 completed', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->count(25)->create();
    Workout::factory()->for($user)->completed()->count(15)->create();

    $request = makeResourceRequest(['userId' => (string) $user->id]);

    $response = $this->resource->handle($request);

    $text = (string) $response->content();
    $upcomingCount = substr_count($text, 'Scheduled:');
    $completedCount = substr_count($text, 'RPE:');

    expect($upcomingCount)->toBe(20)
        ->and($completedCount)->toBe(10);
});

it('respects custom upcoming_limit parameter', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->count(10)->create();

    $request = makeResourceRequest([
        'userId' => (string) $user->id,
        'upcoming_limit' => '5',
    ]);

    $response = $this->resource->handle($request);

    $text = (string) $response->content();
    $upcomingCount = substr_count($text, 'Scheduled:');

    expect($upcomingCount)->toBe(5);
});

it('respects custom completed_limit parameter', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->completed()->count(10)->create();

    $request = makeResourceRequest([
        'userId' => (string) $user->id,
        'completed_limit' => '3',
    ]);

    $response = $this->resource->handle($request);

    $text = (string) $response->content();
    $completedCount = substr_count($text, 'RPE:');

    expect($completedCount)->toBe(3);
});

it('caps limits at 50', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->upcoming()->count(55)->create();

    $request = makeResourceRequest([
        'userId' => (string) $user->id,
        'upcoming_limit' => '100',
    ]);

    $response = $this->resource->handle($request);

    $text = (string) $response->content();
    $upcomingCount = substr_count($text, 'Scheduled:');

    expect($upcomingCount)->toBe(50);
});

it('returns error for missing user id', function () {
    $request = makeResourceRequest([]);

    $response = $this->resource->handle($request);

    expect((string) $response->content())->toContain('User ID is required');
});

it('returns error for non-existent user', function () {
    $request = makeResourceRequest(['userId' => '99999']);

    $response = $this->resource->handle($request);

    expect((string) $response->content())->toContain('User not found');
});

/**
 * @param  array<string, string>  $arguments
 */
function makeResourceRequest(array $arguments): Request
{
    $request = app(Request::class);
    $request->merge($arguments);

    return $request;
}
