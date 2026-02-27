<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Livewire\Injury\Show;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutInjuryPainScore;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('renders the injury detail view', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['injury' => $injury])
        ->assertOk()
        ->assertSee('Knee')
        ->assertSee('Acute');
});

it('shows injury data including body part, type, and severity', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Shoulder,
        'injury_type' => InjuryType::Chronic,
        'severity' => Severity::Moderate,
        'side' => Side::Left,
        'started_at' => '2026-01-15',
        'how_it_happened' => 'Overhead press with bad form',
        'current_symptoms' => 'Dull ache when raising arm',
    ]);

    actingAs($user);

    Livewire::test(Show::class, ['injury' => $injury])
        ->assertSee('Shoulder')
        ->assertSee('Chronic')
        ->assertSee('Moderate')
        ->assertSee('Left')
        ->assertSee('Overhead press with bad form')
        ->assertSee('Dull ache when raising arm');
});

it('shows status label as Active for an active injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();

    actingAs($user);

    $component = Livewire::test(Show::class, ['injury' => $injury]);

    expect($component->instance()->statusLabel)->toBe('Active');
});

it('shows status label as Recovering for a recently resolved injury', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create([
        'ended_at' => now()->subDays(10),
    ]);

    actingAs($user);

    $component = Livewire::test(Show::class, ['injury' => $injury]);

    expect($component->instance()->statusLabel)->toBe('Recovering');
});

it('shows status label as Healed for an injury ended more than 30 days ago', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create([
        'started_at' => now()->subDays(90),
        'ended_at' => now()->subDays(60),
    ]);

    actingAs($user);

    $component = Livewire::test(Show::class, ['injury' => $injury]);

    expect($component->instance()->statusLabel)->toBe('Healed');
});

it('shows latest pain score when available', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    WorkoutInjuryPainScore::factory()->create([
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'pain_score' => 7,
    ]);

    actingAs($user);

    $component = Livewire::test(Show::class, ['injury' => $injury]);

    expect($component->instance()->latestPainScore)->not->toBeNull()
        ->and($component->instance()->latestPainScore->pain_score)->toBe(7);

    $component->assertSee('7');
});

it('shows empty state for pain scores when none exist', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();

    actingAs($user);

    $component = Livewire::test(Show::class, ['injury' => $injury]);

    expect($component->instance()->latestPainScore)->toBeNull();

    $component->assertSee('No pain scores recorded yet');
});

it('prevents viewing another user injury', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->active()->create();

    actingAs($user)
        ->get(route('injuries.show', $injury))
        ->assertForbidden();
});
