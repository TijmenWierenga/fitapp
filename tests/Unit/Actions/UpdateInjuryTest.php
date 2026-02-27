<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

use App\Actions\UpdateInjury;
use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Models\Injury;
use App\Models\User;
use Carbon\CarbonImmutable;

use function Pest\Laravel\assertDatabaseHas;

it('updates an injury with all fields', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
        'severity' => Severity::Mild,
        'side' => Side::Left,
    ]);

    $action = new UpdateInjury;

    $updated = $action->execute(
        injury: $injury,
        injuryType: InjuryType::Chronic,
        bodyPart: BodyPart::Shoulder,
        startedAt: CarbonImmutable::parse('2026-01-10'),
        severity: Severity::Severe,
        side: Side::Right,
        endedAt: CarbonImmutable::parse('2026-02-20'),
        notes: 'Updated notes',
        howItHappened: 'Updated cause',
        currentSymptoms: 'Updated symptoms',
    );

    expect($updated->injury_type)->toBe(InjuryType::Chronic)
        ->and($updated->body_part)->toBe(BodyPart::Shoulder)
        ->and($updated->severity)->toBe(Severity::Severe)
        ->and($updated->side)->toBe(Side::Right)
        ->and($updated->notes)->toBe('Updated notes')
        ->and($updated->how_it_happened)->toBe('Updated cause')
        ->and($updated->current_symptoms)->toBe('Updated symptoms');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'injury_type' => 'chronic',
        'body_part' => 'shoulder',
        'severity' => 'severe',
        'side' => 'right',
        'started_at' => '2026-01-10 00:00:00',
        'ended_at' => '2026-02-20 00:00:00',
        'notes' => 'Updated notes',
        'how_it_happened' => 'Updated cause',
        'current_symptoms' => 'Updated symptoms',
    ]);
});

it('clears optional fields when set to null', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
        'severity' => Severity::Moderate,
        'side' => Side::Left,
        'notes' => 'Some notes',
        'how_it_happened' => 'Running',
        'current_symptoms' => 'Swelling',
        'ended_at' => now()->subDays(5),
    ]);

    $action = new UpdateInjury;

    $updated = $action->execute(
        injury: $injury,
        injuryType: InjuryType::Acute,
        bodyPart: BodyPart::Knee,
        startedAt: CarbonImmutable::parse($injury->started_at),
        severity: null,
        side: null,
        endedAt: null,
        notes: null,
        howItHappened: null,
        currentSymptoms: null,
    );

    expect($updated->severity)->toBeNull()
        ->and($updated->side)->toBeNull()
        ->and($updated->ended_at)->toBeNull()
        ->and($updated->notes)->toBeNull()
        ->and($updated->how_it_happened)->toBeNull()
        ->and($updated->current_symptoms)->toBeNull();

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'severity' => null,
        'side' => null,
        'ended_at' => null,
        'notes' => null,
        'how_it_happened' => null,
        'current_symptoms' => null,
    ]);
});
