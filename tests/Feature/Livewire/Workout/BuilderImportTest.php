<?php

declare(strict_types=1);

use App\Enums\Fit\GarminExerciseCategory;
use App\Enums\FitImportStatus;
use App\Enums\Workout\Activity;
use App\Enums\Workout\WorkoutSource;
use App\Livewire\Workout\Builder;
use App\Models\FitImport;
use App\Models\User;
use Livewire\Livewire;
use Tests\Support\FitActivityFixtureBuilder;

function createPendingImport(User $user, string $fitData): FitImport
{
    return FitImport::create([
        'user_id' => $user->id,
        'status' => FitImportStatus::Pending,
        'raw_data' => base64_encode($fitData),
    ]);
}

it('hydrates builder from import context for a running activity', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000, totalCalories: 300)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSet('importId', (string) $fitImport->id)
        ->assertSet('activity', Activity::Run)
        ->assertNotSet('name', '')
        ->assertCount('sections', 1)
        ->assertSet('sections.0.name', 'Workout')
        ->assertSee('Importing from Garmin FIT');
});

it('hydrates builder from import context for a strength activity with sets', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, totalCalories: 400)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 1, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 1, repetitions: 8, weight: 85.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSet('activity', Activity::Strength)
        ->assertCount('sections', 1)
        ->assertSet('sections.0.blocks.0.exercises.0.type', 'strength')
        ->assertSet('sections.0.blocks.0.exercises.0.target_sets', 3);
});

it('uses workout name from FIT file when available', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, workoutName: 'Push Day')
        ->addSet(setType: 1, repetitions: 10, weight: 50.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSet('name', 'Push Day');
});

it('generates workout name when FIT file has no workout name', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    $component = Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class);

    expect($component->get('name'))->toStartWith('Run - ');
});

it('shows expired error when import does not exist', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::withQueryParams(['import' => '999999'])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSet('importId', null)
        ->assertCount('sections', 3);
});

it('shows RPE and feeling fields in import mode', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSee('Rate of Perceived Exertion')
        ->assertSee('Overall Feeling');
});

it('does not show RPE and feeling fields in normal create mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->assertDontSee('Rate of Perceived Exertion')
        ->assertDontSee('Overall Feeling');
});

it('saves imported workout via FinalizeGarminImport and redirects', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000, totalCalories: 300, avgHeartRate: 150, maxHeartRate: 175)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000, avgHeartRate: 150, maxHeartRate: 175)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'activity' => Activity::Run->value,
        'source' => WorkoutSource::GarminFit,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $fitImport->refresh();
    expect($fitImport->status)->toBe(FitImportStatus::Completed)
        ->and($fitImport->workout_id)->not->toBeNull()
        ->and($fitImport->imported_at)->not->toBeNull();
});

it('shows error when import record is missing during save', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    $component = Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class);

    // Delete the import before saving
    $fitImport->delete();

    $component
        ->call('saveWorkout')
        ->assertHasErrors('importId');
});

it('displays Import Workout heading in import mode', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $fitImport = createPendingImport($user, $fitData);

    Livewire::withQueryParams(['import' => $fitImport->id])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSee('Import Workout')
        ->assertSee('Save & Complete');
});

it('displays Create Workout heading in normal mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->assertSee('Create Workout')
        ->assertSee('Save Workout');
});
