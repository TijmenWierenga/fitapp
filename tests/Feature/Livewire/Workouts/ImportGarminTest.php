<?php

declare(strict_types=1);

use App\DataTransferObjects\Fit\FitImportContext;
use App\Enums\Workout\Activity;
use App\Livewire\Workouts\ImportGarmin;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

    // Verify that a FitImportContext was stored in cache
    $redirectUrl = $component->effects['redirect'];
    preg_match('/import=([a-f0-9-]+)/', $redirectUrl, $matches);

    expect($matches)->toHaveCount(2);

    $uuid = $matches[1];
    $context = Cache::get("fit_import:{$uuid}");

    expect($context)->toBeInstanceOf(FitImportContext::class)
        ->and($context->parsedActivity->session->sport)->toBe(1);
});

it('shows duplicate warning step when a matching imported workout exists', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'source' => 'garmin_fit',
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
        'source' => 'garmin_fit',
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
    preg_match('/import=([a-f0-9-]+)/', $redirectUrl, $matches);

    expect($matches)->toHaveCount(2);

    $uuid = $matches[1];
    $context = Cache::get("fit_import:{$uuid}");

    expect($context)->toBeInstanceOf(FitImportContext::class);
});

it('resets to upload step when user cancels duplicate warning', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->for($user)->create([
        'activity' => Activity::Run,
        'source' => 'garmin_fit',
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
