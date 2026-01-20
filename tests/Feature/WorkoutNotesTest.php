<?php

use App\Models\User;
use App\Models\Workout;

it('can create a workout with notes', function () {
    $user = User::factory()->create();

    $workout = Workout::factory()->for($user)->create([
        'notes' => 'Focus on maintaining cadence throughout the run.',
    ]);

    expect($workout->notes)->toBe('Focus on maintaining cadence throughout the run.');
});

it('can create a workout without notes', function () {
    $user = User::factory()->create();

    $workout = Workout::factory()->for($user)->create([
        'notes' => null,
    ]);

    expect($workout->notes)->toBeNull();
});

it('can update workout notes', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Original notes',
        'completed_at' => null,
    ]);

    $workout->update(['notes' => 'Updated notes']);

    expect($workout->fresh()->notes)->toBe('Updated notes');
});

it('can remove workout notes by setting to null', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Some notes',
        'completed_at' => null,
    ]);

    $workout->update(['notes' => null]);

    expect($workout->fresh()->notes)->toBeNull();
});

it('can remove workout notes by setting to empty string', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Some notes',
        'completed_at' => null,
    ]);

    $workout->update(['notes' => '']);

    expect($workout->fresh()->notes)->toBeNull();
});

it('trims whitespace from notes', function () {
    $workout = Workout::factory()->create([
        'notes' => '  Notes with leading and trailing spaces  ',
    ]);

    expect($workout->notes)->toBe('Notes with leading and trailing spaces');
});

it('converts whitespace-only notes to null', function () {
    $workout = Workout::factory()->create([
        'notes' => '   ',
    ]);

    expect($workout->notes)->toBeNull();
});

it('duplicates workout with notes', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Important workout notes',
    ]);

    $duplicatedWorkout = $workout->duplicate(now()->addDay());

    expect($duplicatedWorkout->notes)->toBe('Important workout notes');
});

it('duplicates workout without notes', function () {
    $workout = Workout::factory()->create([
        'notes' => null,
    ]);

    $duplicatedWorkout = $workout->duplicate(now()->addDay());

    expect($duplicatedWorkout->notes)->toBeNull();
});

it('stores notes with maximum length', function () {
    $longNotes = str_repeat('a', 65535);

    $workout = Workout::factory()->create([
        'notes' => $longNotes,
    ]);

    expect($workout->notes)->toBe($longNotes);
});
