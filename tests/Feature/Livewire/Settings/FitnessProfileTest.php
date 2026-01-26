<?php

use App\Enums\BodyPart;
use App\Enums\FitnessGoal;
use App\Enums\InjuryType;
use App\Livewire\Settings\FitnessProfile;
use App\Models\FitnessProfile as FitnessProfileModel;
use App\Models\Injury;
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

it('adds a new injury', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('openInjuryModal')
        ->assertSet('showInjuryModal', true)
        ->set('injuryType', 'acute')
        ->set('bodyPart', 'knee')
        ->set('startedAt', '2024-01-15')
        ->set('injuryNotes', 'Running injury')
        ->call('saveInjury')
        ->assertHasNoErrors()
        ->assertSet('showInjuryModal', false);

    $this->assertDatabaseHas('injuries', [
        'user_id' => $user->id,
        'injury_type' => 'acute',
        'body_part' => 'knee',
        'notes' => 'Running injury',
    ]);
});

it('edits an existing injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->create([
        'user_id' => $user->id,
        'injury_type' => InjuryType::Acute,
        'body_part' => BodyPart::Knee,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('openInjuryModal', $injury->id)
        ->assertSet('editingInjuryId', $injury->id)
        ->assertSet('injuryType', 'acute')
        ->assertSet('bodyPart', 'knee')
        ->set('injuryType', 'chronic')
        ->set('bodyPart', 'ankle')
        ->call('saveInjury')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'injury_type' => 'chronic',
        'body_part' => 'ankle',
    ]);
});

it('deletes an injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('deleteInjury', $injury->id)
        ->assertDispatched('injury-deleted');

    $this->assertDatabaseMissing('injuries', ['id' => $injury->id]);
});

it('validates injury required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('openInjuryModal')
        ->set('injuryType', '')
        ->set('bodyPart', '')
        ->set('startedAt', '')
        ->call('saveInjury')
        ->assertHasErrors(['injuryType', 'bodyPart', 'startedAt']);
});

it('validates injury end date is after start date', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('openInjuryModal')
        ->set('injuryType', 'acute')
        ->set('bodyPart', 'knee')
        ->set('startedAt', '2024-06-01')
        ->set('endedAt', '2024-01-01')
        ->call('saveInjury')
        ->assertHasErrors(['endedAt']);
});

it('closes the injury modal and resets form', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('openInjuryModal')
        ->set('injuryType', 'acute')
        ->set('bodyPart', 'knee')
        ->call('closeInjuryModal')
        ->assertSet('showInjuryModal', false)
        ->assertSet('injuryType', null)
        ->assertSet('bodyPart', null);
});

it('displays injuries in the table', function () {
    $user = User::factory()->create();
    Injury::factory()->create([
        'user_id' => $user->id,
        'body_part' => BodyPart::Shoulder,
        'injury_type' => InjuryType::Chronic,
    ]);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->assertSee('Shoulder')
        ->assertSee('Chronic');
});

it('cannot access another users injuries', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $injury = Injury::factory()->create(['user_id' => $otherUser->id]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::actingAs($user)
        ->test(FitnessProfile::class)
        ->call('openInjuryModal', $injury->id);
});
