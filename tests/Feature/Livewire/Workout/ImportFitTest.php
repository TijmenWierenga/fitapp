<?php

declare(strict_types=1);

use App\Enums\Fit\GarminExerciseCategory;
use App\Enums\Workout\Activity;
use App\Livewire\Workout\ImportFit;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Support\FitActivityFixtureBuilder;

it('opens the modal for an incomplete workout belonging to the user', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()]);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->assertSet('workout.id', $workout->id);
});

it('does not open the modal for a completed workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->assertSet('showModal', false);
});

it('throws when trying to open the modal for another user workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $otherUser = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($otherUser)->create(['scheduled_at' => now()]);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id);
})->throws(ModelNotFoundException::class);

it('resets all state when closing the modal', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()]);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('workout', null)
        ->assertSet('step', 'upload')
        ->assertSet('preview', null)
        ->assertSet('rpe', null)
        ->assertSet('feeling', null);
});

it('parses a valid FIT file and transitions to preview step', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'activity' => Activity::Strength,
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, totalCalories: 400, avgHeartRate: 130, maxHeartRate: 165)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->set('fitFile', $uploadedFile)
        ->assertSet('step', 'preview')
        ->assertNotSet('preview', null);
});

it('imports FIT data into the workout and marks it completed', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'activity' => Activity::Strength,
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, totalCalories: 400, avgHeartRate: 130, maxHeartRate: 165)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->set('fitFile', $uploadedFile)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('confirmImport')
        ->assertDispatched('fit-imported')
        ->assertRedirect(route('workouts.show', $workout));

    $workout->refresh();

    expect($workout->isCompleted())->toBeTrue()
        ->and($workout->total_duration)->toBe(3600)
        ->and($workout->total_calories)->toBe(400)
        ->and($workout->avg_heart_rate)->toBe(130)
        ->and($workout->max_heart_rate)->toBe(165)
        ->and($workout->source)->toBe('garmin_fit')
        ->and($workout->rpe)->toBe(7)
        ->and($workout->feeling)->toBe(4);
});

it('imports without rpe and feeling when they are not provided', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'activity' => Activity::Strength,
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800, totalCalories: 200)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->set('fitFile', $uploadedFile)
        ->call('confirmImport')
        ->assertDispatched('fit-imported');

    $workout->refresh();

    expect($workout->isCompleted())->toBeTrue()
        ->and($workout->rpe)->toBeNull()
        ->and($workout->feeling)->toBeNull();
});

it('shows duplicate warning when a matching imported workout exists', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    // Existing imported workout
    Workout::factory()->for($user)->create([
        'activity' => Activity::Strength,
        'source' => 'garmin_fit',
        'scheduled_at' => now(),
    ]);

    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'activity' => Activity::Strength,
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, totalCalories: 400)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    Livewire::actingAs($user)
        ->test(ImportFit::class)
        ->dispatch('import-fit', workoutId: $workout->id)
        ->set('fitFile', $uploadedFile)
        ->assertSet('step', 'preview')
        ->assertNotSet('duplicateWarning', null);
});
