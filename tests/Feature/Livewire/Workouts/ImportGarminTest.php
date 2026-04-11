<?php

declare(strict_types=1);

use App\Enums\FitImportStatus;
use App\Enums\Workout\Activity;
use App\Enums\Workout\WorkoutSource;
use App\Livewire\Workouts\ImportGarmin;
use App\Models\FitImport;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Support\FitActivityFixtureBuilder;

it('renders the import page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('workouts.import'))
        ->assertOk();
});

it('redirects unauthenticated users', function () {
    $this->get(route('workouts.import'))
        ->assertRedirect();
});

it('shows the upload step initially', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->assertSet('step', 'upload')
        ->assertSee('Upload .FIT file');
});

it('redirects to builder after uploading a valid FIT file without duplicates', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    $component = Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->set('fitFile', $uploadedFile)
        ->assertRedirect();

    // Verify that a pending FitImport was created
    $redirectUrl = $component->effects['redirect'];
    preg_match('/import=(\d+)/', $redirectUrl, $matches);

    expect($matches)->toHaveCount(2);

    $fitImport = FitImport::find($matches[1]);

    expect($fitImport)->not->toBeNull()
        ->and($fitImport->status)->toBe(FitImportStatus::Pending)
        ->and($fitImport->user_id)->toBe($user->id);
});

it('shows duplicate warning step when a matching imported workout exists', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'source' => WorkoutSource::GarminFit,
        'scheduled_at' => now(),
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->set('fitFile', $uploadedFile)
        ->assertSet('step', 'duplicate_warning')
        ->assertNotSet('duplicateWarning', null)
        ->assertSee('Possible duplicate');
});

it('redirects to builder when user confirms import despite duplicate', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'source' => WorkoutSource::GarminFit,
        'scheduled_at' => now(),
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    $component = Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->set('fitFile', $uploadedFile)
        ->assertSet('step', 'duplicate_warning')
        ->call('confirmImportAnyway')
        ->assertRedirect();

    $redirectUrl = $component->effects['redirect'];
    preg_match('/import=(\d+)/', $redirectUrl, $matches);

    expect($matches)->toHaveCount(2);

    $fitImport = FitImport::find($matches[1]);
    expect($fitImport)->not->toBeNull()
        ->and($fitImport->status)->toBe(FitImportStatus::Pending);
});

it('resets to upload step and deletes pending import when user cancels duplicate warning', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'source' => WorkoutSource::GarminFit,
        'scheduled_at' => now(),
    ]);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 1800, totalDistance: 5000)
        ->build();

    $uploadedFile = UploadedFile::fake()->createWithContent('test.fit', $fitData);

    Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->set('fitFile', $uploadedFile)
        ->assertSet('step', 'duplicate_warning')
        ->call('resetImport')
        ->assertSet('step', 'upload')
        ->assertSet('duplicateWarning', null);

    // Pending import should be deleted
    expect(FitImport::where('user_id', $user->id)->where('status', FitImportStatus::Pending)->count())->toBe(0);
});

it('shows parse error for invalid FIT file', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $uploadedFile = UploadedFile::fake()->createWithContent('bad.fit', 'not a fit file');

    Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->set('fitFile', $uploadedFile)
        ->assertSet('step', 'upload')
        ->assertNotSet('parseError', null);
});
