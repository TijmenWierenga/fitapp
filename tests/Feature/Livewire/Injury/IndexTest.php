<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Livewire\Injury\Index;
use App\Models\Injury;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('renders the injuries index page', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Injuries');
});

it('displays stats cards with correct counts', function () {
    $user = User::factory()->create();

    // Active injury
    Injury::factory()->for($user)->active()->create();

    // Recovering (ended within last 30 days)
    Injury::factory()->for($user)->create([
        'ended_at' => now()->subDays(10),
    ]);

    // Healed (ended more than 30 days ago)
    Injury::factory()->for($user)->create([
        'ended_at' => now()->subDays(60),
    ]);

    actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Active Injuries')
        ->assertSee('Recovering')
        ->assertSee('Healed');
});

it('lists injuries in the table', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
    ]);

    actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Knee')
        ->assertSee('Acute');
});

it('does not show injuries from other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Injury::factory()->for($other)->create();

    actingAs($user);

    $component = Livewire::test(Index::class);

    expect($component->instance()->injuries)->toHaveCount(0);
});

it('can open the log injury modal', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openLogModal')
        ->assertSet('showInjuryModal', true)
        ->assertSet('editingInjuryId', null)
        ->assertSet('side', Side::NotApplicable->value);
});

it('can create a new injury', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openLogModal')
        ->set('bodyPart', BodyPart::Knee->value)
        ->set('side', Side::Left->value)
        ->set('injuryType', InjuryType::Acute->value)
        ->set('severity', Severity::Moderate->value)
        ->set('startedAt', '2026-02-15')
        ->set('howItHappened', 'Running on uneven ground')
        ->set('currentSymptoms', 'Swelling and pain')
        ->call('saveInjury')
        ->assertSet('showInjuryModal', false);

    assertDatabaseHas('injuries', [
        'user_id' => $user->id,
        'body_part' => BodyPart::Knee->value,
        'side' => Side::Left->value,
        'injury_type' => InjuryType::Acute->value,
        'severity' => Severity::Moderate->value,
        'started_at' => '2026-02-15 00:00:00',
        'how_it_happened' => 'Running on uneven ground',
        'current_symptoms' => 'Swelling and pain',
    ]);
});

it('validates required fields when creating an injury', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openLogModal')
        ->set('startedAt', null)
        ->call('saveInjury')
        ->assertHasErrors(['bodyPart', 'injuryType', 'startedAt']);
});

it('can open the edit modal with injury data', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Hamstring,
        'side' => Side::Right,
        'injury_type' => InjuryType::Chronic,
        'severity' => Severity::Mild,
        'started_at' => '2026-01-10',
        'how_it_happened' => 'Overhead press',
        'current_symptoms' => 'Dull ache',
        'notes' => 'Improving slowly',
    ]);

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openEditModal', $injury->id)
        ->assertSet('showInjuryModal', true)
        ->assertSet('editingInjuryId', $injury->id)
        ->assertSet('bodyPart', BodyPart::Hamstring->value)
        ->assertSet('side', Side::Right->value)
        ->assertSet('injuryType', InjuryType::Chronic->value)
        ->assertSet('severity', Severity::Mild->value)
        ->assertSet('startedAt', '2026-01-10')
        ->assertSet('howItHappened', 'Overhead press')
        ->assertSet('currentSymptoms', 'Dull ache')
        ->assertSet('injuryNotes', 'Improving slowly')
        ->assertSet('statusUpdate', 'active');
});

it('can update an existing injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
    ]);

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openEditModal', $injury->id)
        ->set('severity', Severity::Severe->value)
        ->set('injuryNotes', 'Getting worse')
        ->set('statusUpdate', 'active')
        ->call('saveInjury')
        ->assertSet('showInjuryModal', false);

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'severity' => Severity::Severe->value,
        'notes' => 'Getting worse',
        'ended_at' => null,
    ]);
});

it('sets ended_at to today when status changes to recovering', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Ankle,
        'injury_type' => InjuryType::Acute,
        'started_at' => '2026-01-15',
    ]);

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openEditModal', $injury->id)
        ->set('statusUpdate', 'recovering')
        ->call('saveInjury')
        ->assertSet('showInjuryModal', false);

    $injury->refresh();
    expect($injury->ended_at)->not->toBeNull()
        ->and($injury->ended_at->toDateString())->toBe(now()->toDateString());
});

it('clears ended_at when status changes back to active', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create([
        'body_part' => BodyPart::Hip,
        'injury_type' => InjuryType::Recurring,
        'started_at' => '2026-01-01',
        'ended_at' => now()->subDays(5),
    ]);

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openEditModal', $injury->id)
        ->set('statusUpdate', 'active')
        ->call('saveInjury')
        ->assertSet('showInjuryModal', false);

    $injury->refresh();
    expect($injury->ended_at)->toBeNull();
});

it('can mark an injury as healed', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openEditModal', $injury->id)
        ->call('markAsHealed', $injury->id);

    $injury->refresh();
    expect($injury->ended_at)->not->toBeNull()
        ->and($injury->ended_at->toDateString())->toBe(now()->toDateString());
});

it('can delete an injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('deleteInjury', $injury->id);

    assertDatabaseMissing('injuries', ['id' => $injury->id]);
});

it('cannot edit another user\'s injury', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->active()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('openEditModal', $injury->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('cannot delete another user\'s injury', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->call('deleteInjury', $injury->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('shows empty state when no injuries exist', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('No injuries recorded');
});
