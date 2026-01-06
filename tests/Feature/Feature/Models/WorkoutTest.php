<?php

use App\Models\User;
use App\Models\Workout;

it('duplicates a workout with a new scheduled date', function () {
    $user = User::factory()->create();
    $originalWorkout = Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Strength Training',
        'scheduled_at' => '2026-01-10 09:00:00',
        'completed_at' => null,
    ]);

    $newScheduledAt = new DateTime('2026-01-17 09:00:00');
    $duplicatedWorkout = $originalWorkout->duplicate($newScheduledAt);

    expect($duplicatedWorkout)
        ->toBeInstanceOf(Workout::class)
        ->id->not->toBe($originalWorkout->id)
        ->user_id->toBe($user->id)
        ->name->toBe('Strength Training')
        ->scheduled_at->format('Y-m-d H:i:s')->toBe('2026-01-17 09:00:00')
        ->completed_at->toBeNull();

    expect(Workout::count())->toBe(2);
});

it('duplicates a completed workout as uncompleted', function () {
    $user = User::factory()->create();
    $originalWorkout = Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cardio',
        'scheduled_at' => '2026-01-05 17:00:00',
        'completed_at' => '2026-01-05 18:00:00',
    ]);

    $newScheduledAt = new DateTime('2026-01-12 17:00:00');
    $duplicatedWorkout = $originalWorkout->duplicate($newScheduledAt);

    expect($duplicatedWorkout)
        ->name->toBe('Cardio')
        ->completed_at->toBeNull();
});

it('preserves the user association when duplicating', function () {
    $user = User::factory()->create();
    $originalWorkout = Workout::factory()->create([
        'user_id' => $user->id,
    ]);

    $newScheduledAt = new DateTime('2026-02-01 10:00:00');
    $duplicatedWorkout = $originalWorkout->duplicate($newScheduledAt);

    expect($duplicatedWorkout->user->id)->toBe($user->id);
});
