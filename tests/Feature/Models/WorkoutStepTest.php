<?php

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutStep;

test('can create a basic workout step', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $step = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1000,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    expect($step->workout_id)->toBe($workout->id)
        ->and($step->step_kind)->toBe('run')
        ->and($step->intensity)->toBe('active')
        ->and($step->duration_value)->toBe(1000);
});

test('can create a repeat step with children', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $repeat = $workout->allSteps()->create([
        'step_kind' => 'repeat',
        'intensity' => 'active',
        'repeat_count' => 3,
        'sort_order' => 0,
    ]);

    $child = $workout->allSteps()->create([
        'parent_step_id' => $repeat->id,
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1000,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    expect($repeat->isRepeat())->toBeTrue()
        ->and($repeat->children)->toHaveCount(1)
        ->and($child->parent_step_id)->toBe($repeat->id);
});

test('validates normal steps cannot have children', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $normalStep = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1000,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    $workout->allSteps()->create([
        'parent_step_id' => $normalStep->id,
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 500,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    $normalStep->refresh();
    $errors = $normalStep->validate();

    expect($errors)->toContain('Normal steps cannot have children');
});

test('validates repeat steps must have at least 1 child', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $repeat = $workout->allSteps()->create([
        'step_kind' => 'repeat',
        'intensity' => 'active',
        'repeat_count' => 2,
        'sort_order' => 0,
    ]);

    $errors = $repeat->validate();

    expect($errors)->toContain('Repeat steps must have at least 1 child');
});

test('validates time duration range', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $tooShort = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'time',
        'duration_value' => 5,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    $tooLong = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'time',
        'duration_value' => 25000,
        'target_type' => 'none',
        'sort_order' => 1,
    ]);

    expect($tooShort->validate())->toContain('Time duration must be between 10 seconds and 6 hours')
        ->and($tooLong->validate())->toContain('Time duration must be between 10 seconds and 6 hours');
});

test('validates distance must be divisible by 10', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $invalidDistance = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1005,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    expect($invalidDistance->validate())->toContain('Distance must be divisible by 10');
});

test('validates heart rate range', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $invalidHR = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1000,
        'target_type' => 'heart_rate',
        'target_mode' => 'range',
        'target_low' => 150,
        'target_high' => 120,
        'sort_order' => 0,
    ]);

    expect($invalidHR->validate())->toContain('HR low must be less than high');
});

test('validates pace range', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $invalidPace = $workout->allSteps()->create([
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1000,
        'target_type' => 'pace',
        'target_mode' => 'range',
        'target_low' => 300,
        'target_high' => 270,
        'sort_order' => 0,
    ]);

    expect($invalidPace->validate())->toContain('Pace low must be less than high');
});

test('validates repeat count minimum', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $invalidRepeat = $workout->allSteps()->create([
        'step_kind' => 'repeat',
        'intensity' => 'active',
        'repeat_count' => 1,
        'sort_order' => 0,
    ]);

    expect($invalidRepeat->validate())->toContain('Repeat count must be at least 2');
});

test('workout can duplicate with steps', function () {
    $user = User::factory()->create();
    $workout = $user->workouts()->create([
        'name' => 'Test Workout',
        'sport' => 'running',
        'scheduled_at' => now(),
    ]);

    $workout->allSteps()->create([
        'step_kind' => 'warmup',
        'intensity' => 'warmup',
        'duration_type' => 'time',
        'duration_value' => 300,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    $repeat = $workout->allSteps()->create([
        'step_kind' => 'repeat',
        'intensity' => 'active',
        'repeat_count' => 3,
        'sort_order' => 1,
    ]);

    $workout->allSteps()->create([
        'parent_step_id' => $repeat->id,
        'step_kind' => 'run',
        'intensity' => 'active',
        'duration_type' => 'distance',
        'duration_value' => 1000,
        'target_type' => 'none',
        'sort_order' => 0,
    ]);

    $duplicated = $workout->duplicate(now()->addDay());

    expect($duplicated->name)->toBe('Test Workout')
        ->and($duplicated->sport)->toBe('running')
        ->and($duplicated->steps)->toHaveCount(2)
        ->and($duplicated->allSteps)->toHaveCount(3);
});
