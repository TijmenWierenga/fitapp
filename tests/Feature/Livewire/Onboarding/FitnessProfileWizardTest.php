<?php

use App\Enums\ExperienceLevel;
use App\Enums\FitnessGoal;
use App\Livewire\Onboarding\FitnessProfileWizard;
use App\Models\FitnessProfile;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('renders the onboarding wizard', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('onboarding'))
        ->assertOk()
        ->assertSeeLivewire(FitnessProfileWizard::class);
});

it('requires authentication', function () {
    $this->get(route('onboarding'))
        ->assertRedirect(route('login'));
});

it('starts on step 1', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->assertSet('currentStep', 1)
        ->assertSee('Experience Level');
});

it('validates step 1 requires experience level', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->set('experienceLevel', '')
        ->call('nextStep')
        ->assertHasErrors(['experienceLevel' => 'required'])
        ->assertSet('currentStep', 1);
});

it('advances from step 1 to step 2', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->set('experienceLevel', 'beginner')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2)
        ->assertSee('Primary Fitness Goal');
});

it('validates step 2 requires primary goal and schedule', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->set('experienceLevel', 'beginner')
        ->call('nextStep')
        ->set('primaryGoal', '')
        ->call('nextStep')
        ->assertHasErrors(['primaryGoal' => 'required'])
        ->assertSet('currentStep', 2);
});

it('advances from step 2 to step 3', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->set('experienceLevel', 'intermediate')
        ->call('nextStep')
        ->set('primaryGoal', 'muscle_gain')
        ->set('availableDaysPerWeek', 5)
        ->set('minutesPerSession', 60)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 3)
        ->assertSee('I have access to a gym');
});

it('can navigate back to previous steps', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->set('experienceLevel', 'advanced')
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->call('previousStep')
        ->assertSet('currentStep', 1);
});

it('creates a fitness profile on completion', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->set('experienceLevel', 'beginner')
        ->set('dateOfBirth', '1990-05-15')
        ->set('biologicalSex', 'male')
        ->set('bodyWeightKg', '80')
        ->set('heightCm', 180)
        ->call('nextStep')
        ->set('primaryGoal', 'weight_loss')
        ->set('goalDetails', 'Lose 10kg')
        ->set('availableDaysPerWeek', 4)
        ->set('minutesPerSession', 60)
        ->call('nextStep')
        ->set('hasGymAccess', true)
        ->set('homeEquipment', ['dumbbell', 'bands'])
        ->set('preferGarminExercises', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'experience_level' => 'beginner',
        'biological_sex' => 'male',
        'body_weight_kg' => 80,
        'height_cm' => 180,
        'primary_goal' => 'weight_loss',
        'goal_details' => 'Lose 10kg',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
        'has_gym_access' => true,
        'prefer_garmin_exercises' => true,
    ]);

    $profile = $user->fitnessProfile()->first();
    expect($profile->home_equipment)->toBe(['dumbbell', 'bands']);
    expect($profile->date_of_birth->format('Y-m-d'))->toBe('1990-05-15');
});

it('updates an existing profile on completion', function () {
    $user = User::factory()->create();
    FitnessProfile::factory()->create([
        'user_id' => $user->id,
        'primary_goal' => FitnessGoal::GeneralFitness,
        'experience_level' => ExperienceLevel::Beginner,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->assertSet('experienceLevel', 'beginner')
        ->set('experienceLevel', 'advanced')
        ->call('nextStep')
        ->set('primaryGoal', 'muscle_gain')
        ->call('nextStep')
        ->call('save')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'experience_level' => 'advanced',
        'primary_goal' => 'muscle_gain',
    ]);
});

it('loads existing profile data on mount', function () {
    $user = User::factory()->create();
    FitnessProfile::factory()->create([
        'user_id' => $user->id,
        'primary_goal' => FitnessGoal::Endurance,
        'experience_level' => ExperienceLevel::Intermediate,
        'available_days_per_week' => 5,
        'minutes_per_session' => 90,
        'has_gym_access' => true,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfileWizard::class)
        ->assertSet('experienceLevel', 'intermediate')
        ->assertSet('primaryGoal', 'endurance')
        ->assertSet('availableDaysPerWeek', 5)
        ->assertSet('minutesPerSession', 90)
        ->assertSet('hasGymAccess', true);
});
