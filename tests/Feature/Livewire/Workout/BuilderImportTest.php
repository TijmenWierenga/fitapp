<?php

declare(strict_types=1);

use App\DataTransferObjects\Fit\FitImportContext;
use App\Enums\Fit\GarminExerciseCategory;
use App\Enums\Workout\Activity;
use App\Livewire\Workout\Builder;
use App\Models\User;
use App\Support\Fit\Decode\FitActivityParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\FitActivityFixtureBuilder;

function createImportContext(string $fitData): string
{
    $parsed = app(FitActivityParser::class)->parse($fitData);
    $uuid = (string) Str::uuid();

    Cache::put("fit_import:{$uuid}", new FitImportContext($parsed, $fitData), now()->addMinutes(30));

    return $uuid;
}

it('hydrates builder from import context for a running activity', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000, totalCalories: 300)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uuid = createImportContext($fitData);

    Livewire::withQueryParams(['import' => $uuid])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSet('importContextKey', $uuid)
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

    $uuid = createImportContext($fitData);

    Livewire::withQueryParams(['import' => $uuid])
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

    $uuid = createImportContext($fitData);

    Livewire::withQueryParams(['import' => $uuid])
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

    $uuid = createImportContext($fitData);

    $component = Livewire::withQueryParams(['import' => $uuid])
        ->actingAs($user)
        ->test(Builder::class);

    expect($component->get('name'))->toStartWith('Run - ');
});

it('shows expired error when import context has expired', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::withQueryParams(['import' => 'expired-uuid-that-does-not-exist'])
        ->actingAs($user)
        ->test(Builder::class)
        ->assertSet('importContextKey', null)
        ->assertCount('sections', 3);
});

it('shows RPE and feeling fields in import mode', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uuid = createImportContext($fitData);

    Livewire::withQueryParams(['import' => $uuid])
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

    $uuid = createImportContext($fitData);

    Livewire::withQueryParams(['import' => $uuid])
        ->actingAs($user)
        ->test(Builder::class)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'activity' => Activity::Run->value,
        'source' => 'garmin_fit',
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $this->assertDatabaseHas('fit_imports', [
        'user_id' => $user->id,
    ]);

    // Verify cache was cleared
    expect(Cache::get("fit_import:{$uuid}"))->toBeNull();
});

it('shows error when import context expires during save', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uuid = createImportContext($fitData);

    $component = Livewire::withQueryParams(['import' => $uuid])
        ->actingAs($user)
        ->test(Builder::class);

    // Expire the cache before saving
    Cache::forget("fit_import:{$uuid}");

    $component
        ->call('saveWorkout')
        ->assertHasErrors('importContextKey');
});

it('displays Import Workout heading in import mode', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uuid = createImportContext($fitData);

    Livewire::withQueryParams(['import' => $uuid])
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
