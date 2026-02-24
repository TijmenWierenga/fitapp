<?php

use App\Actions\BuildCoachContext;
use App\Models\FitnessProfile;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('includes fitness profile when present', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->muscleGain()->for($user)->create([
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)
        ->toContain('Fitness Profile')
        ->toContain('Muscle Gain')
        ->toContain('Available days/week:** 4')
        ->toContain('Minutes per session:** 60');
});

it('handles missing profile gracefully', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)
        ->toContain('No fitness profile set up')
        ->toContain('Ask the user about their goals');
});

it('includes active injuries', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->active()->for($user)->create([
        'body_part' => 'knee',
        'injury_type' => 'acute',
        'notes' => 'Swelling after running',
    ]);

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)
        ->toContain('Active Injuries')
        ->toContain('knee')
        ->toContain('acute')
        ->toContain('Swelling after running');
});

it('excludes resolved injuries from active injuries section', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->resolved()->for($user)->create([
        'body_part' => 'shoulder',
    ]);

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)->not->toContain('Active Injuries');
});

it('includes schedule with upcoming and recent workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Workout::factory()->for($user)->create([
        'name' => 'Leg Day Done',
        'scheduled_at' => now()->subDay(),
        'completed_at' => now()->subDay(),
        'rpe' => 7,
    ]);

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)
        ->toContain('Morning Run')
        ->toContain('Leg Day Done')
        ->toContain('RPE 7');
});

it('shows empty schedule message when no workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)->toContain('No upcoming or recently completed workouts');
});

it('includes current date and time section', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();

    $context = app(BuildCoachContext::class)->execute($user);

    expect($context)
        ->toContain('Current Date/Time')
        ->toContain('Timezone:** Europe/Amsterdam');
});
