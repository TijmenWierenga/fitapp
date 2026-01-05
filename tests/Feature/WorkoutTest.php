<?php

use App\Models\User;
use App\Models\Workout;

it('shows upcoming workouts', function () {
    $user = User::factory()->create();

    // Create past, upcoming, and completed workouts
    Workout::factory()->for($user)->create(['scheduled_at' => now()->subDay()]);
    $upcoming1 = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);
    $upcoming2 = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDays(2)]);
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDays(3), 'completed_at' => now()]);

    $upcomingWorkouts = $user->workouts()->upcoming()->get();

    expect($upcomingWorkouts)->toHaveCount(2)
        ->and($upcomingWorkouts->first()->id)->toBe($upcoming1->id)
        ->and($upcomingWorkouts->last()->id)->toBe($upcoming2->id);
});

it('shows completed workouts', function () {
    $user = User::factory()->create();

    Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => null]);
    $completed1 = Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => now()]);
    $completed2 = Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => now()->subDay()]);

    $completedWorkouts = $user->workouts()->completed()->get();

    expect($completedWorkouts)->toHaveCount(2)
        ->and($completedWorkouts->first()->id)->toBe($completed1->id);
});

it('shows overdue workouts', function () {
    $user = User::factory()->create();

    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);
    $overdue1 = Workout::factory()->for($user)->create(['scheduled_at' => now()->subDay()]);
    $overdue2 = Workout::factory()->for($user)->create(['scheduled_at' => now()->subDays(2)]);
    Workout::factory()->for($user)->create(['scheduled_at' => now()->subDays(3), 'completed_at' => now()]);

    $overdueWorkouts = $user->workouts()->overdue()->get();

    expect($overdueWorkouts)->toHaveCount(2)
        ->and($overdueWorkouts->first()->id)->toBe($overdue1->id)
        ->and($overdueWorkouts->last()->id)->toBe($overdue2->id);
});

it('can mark workout as completed', function () {
    $workout = Workout::factory()->create(['completed_at' => null]);

    expect($workout->isCompleted())->toBeFalse();

    $workout->markAsCompleted();

    expect($workout->fresh()->isCompleted())->toBeTrue()
        ->and($workout->fresh()->completed_at)->not->toBeNull();
});

it('checks if workout is completed', function () {
    $incomplete = Workout::factory()->create(['completed_at' => null]);
    $complete = Workout::factory()->create(['completed_at' => now()]);

    expect($incomplete->isCompleted())->toBeFalse()
        ->and($complete->isCompleted())->toBeTrue();
});
