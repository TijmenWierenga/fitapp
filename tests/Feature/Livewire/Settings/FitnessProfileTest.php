<?php

use App\Enums\FitnessGoal;
use App\Livewire\Settings\FitnessProfile;
use App\Models\FitnessProfile as FitnessProfileModel;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('renders the fitness profile page', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('fitness-profile.edit'))
        ->assertOk()
        ->assertSeeLivewire(FitnessProfile::class);
});

it('loads existing fitness profile data on mount', function () {
    $user = User::factory()->create();
    $profile = FitnessProfileModel::factory()->create([
        'user_id' => $user->id,
        'primary_goal' => FitnessGoal::Endurance,
        'goal_details' => 'Run a marathon',
        'available_days_per_week' => 5,
        'minutes_per_session' => 90,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->assertSet('primaryGoal', 'endurance')
        ->assertSet('goalDetails', 'Run a marathon')
        ->assertSet('availableDaysPerWeek', 5)
        ->assertSet('minutesPerSession', 90);
});

it('creates a new fitness profile', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->set('primaryGoal', 'weight_loss')
        ->set('goalDetails', 'Lose 10kg')
        ->set('availableDaysPerWeek', 4)
        ->set('minutesPerSession', 60)
        ->call('saveProfile')
        ->assertHasNoErrors()
        ->assertDispatched('profile-saved');

    $this->assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'primary_goal' => 'weight_loss',
        'goal_details' => 'Lose 10kg',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);
});

it('updates an existing fitness profile', function () {
    $user = User::factory()->create();
    FitnessProfileModel::factory()->create([
        'user_id' => $user->id,
        'primary_goal' => FitnessGoal::WeightLoss,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->set('primaryGoal', 'muscle_gain')
        ->set('goalDetails', 'Build strength')
        ->set('availableDaysPerWeek', 6)
        ->set('minutesPerSession', 120)
        ->call('saveProfile')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'primary_goal' => 'muscle_gain',
        'goal_details' => 'Build strength',
    ]);
});

it('validates fitness profile required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->set('primaryGoal', '')
        ->call('saveProfile')
        ->assertHasErrors(['primaryGoal' => 'required']);
});

it('validates available days per week range', function (int $days, bool $shouldFail) {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->set('primaryGoal', 'general_fitness')
        ->set('availableDaysPerWeek', $days)
        ->set('minutesPerSession', 60)
        ->call('saveProfile');

    if ($shouldFail) {
        $component->assertHasErrors(['availableDaysPerWeek']);
    } else {
        $component->assertHasNoErrors(['availableDaysPerWeek']);
    }
})->with([
    'valid: 1 day' => [1, false],
    'valid: 7 days' => [7, false],
    'invalid: 0 days' => [0, true],
    'invalid: 8 days' => [8, true],
]);

it('validates minutes per session range', function (int $minutes, bool $shouldFail) {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->set('primaryGoal', 'general_fitness')
        ->set('availableDaysPerWeek', 3)
        ->set('minutesPerSession', $minutes)
        ->call('saveProfile');

    if ($shouldFail) {
        $component->assertHasErrors(['minutesPerSession']);
    } else {
        $component->assertHasNoErrors(['minutesPerSession']);
    }
})->with([
    'valid: 15 minutes' => [15, false],
    'valid: 180 minutes' => [180, false],
    'invalid: 14 minutes' => [14, true],
    'invalid: 181 minutes' => [181, true],
]);

it('saves prefer garmin exercises preference', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->set('primaryGoal', 'general_fitness')
        ->set('availableDaysPerWeek', 3)
        ->set('minutesPerSession', 60)
        ->set('preferGarminExercises', true)
        ->call('saveProfile')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'prefer_garmin_exercises' => true,
    ]);
});

it('loads prefer garmin exercises on mount', function () {
    $user = User::factory()->create();
    FitnessProfileModel::factory()->preferGarmin()->create([
        'user_id' => $user->id,
        'primary_goal' => FitnessGoal::GeneralFitness,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->assertSet('preferGarminExercises', true);
});
