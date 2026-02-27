<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

use App\Actions\CreateInjury;
use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Models\Injury;
use App\Models\User;
use Carbon\CarbonImmutable;

use function Pest\Laravel\assertDatabaseHas;

it('creates an injury with all fields', function () {
    $user = User::factory()->create();
    $action = new CreateInjury;

    $injury = $action->execute(
        user: $user,
        injuryType: InjuryType::Acute,
        bodyPart: BodyPart::Knee,
        startedAt: CarbonImmutable::parse('2026-02-15'),
        severity: Severity::Moderate,
        side: Side::Left,
        endedAt: CarbonImmutable::parse('2026-02-20'),
        notes: 'Recovering well',
        howItHappened: 'Running on uneven ground',
        currentSymptoms: 'Mild swelling',
    );

    expect($injury)->toBeInstanceOf(Injury::class)
        ->and($injury->user_id)->toBe($user->id)
        ->and($injury->injury_type)->toBe(InjuryType::Acute)
        ->and($injury->body_part)->toBe(BodyPart::Knee)
        ->and($injury->severity)->toBe(Severity::Moderate)
        ->and($injury->side)->toBe(Side::Left)
        ->and($injury->notes)->toBe('Recovering well')
        ->and($injury->how_it_happened)->toBe('Running on uneven ground')
        ->and($injury->current_symptoms)->toBe('Mild swelling');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'user_id' => $user->id,
        'injury_type' => 'acute',
        'body_part' => 'knee',
        'severity' => 'moderate',
        'side' => 'left',
        'started_at' => '2026-02-15 00:00:00',
        'ended_at' => '2026-02-20 00:00:00',
        'notes' => 'Recovering well',
        'how_it_happened' => 'Running on uneven ground',
        'current_symptoms' => 'Mild swelling',
    ]);
});

it('creates an injury with only required fields', function () {
    $user = User::factory()->create();
    $action = new CreateInjury;

    $injury = $action->execute(
        user: $user,
        injuryType: InjuryType::Chronic,
        bodyPart: BodyPart::LowerBack,
        startedAt: CarbonImmutable::parse('2026-01-01'),
    );

    expect($injury)->toBeInstanceOf(Injury::class)
        ->and($injury->user_id)->toBe($user->id)
        ->and($injury->injury_type)->toBe(InjuryType::Chronic)
        ->and($injury->body_part)->toBe(BodyPart::LowerBack)
        ->and($injury->severity)->toBeNull()
        ->and($injury->side)->toBeNull()
        ->and($injury->ended_at)->toBeNull()
        ->and($injury->notes)->toBeNull()
        ->and($injury->how_it_happened)->toBeNull()
        ->and($injury->current_symptoms)->toBeNull();

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'user_id' => $user->id,
        'injury_type' => 'chronic',
        'body_part' => 'lower_back',
    ]);
});

it('trims whitespace from text fields', function () {
    $user = User::factory()->create();
    $action = new CreateInjury;

    $injury = $action->execute(
        user: $user,
        injuryType: InjuryType::Acute,
        bodyPart: BodyPart::Shoulder,
        startedAt: CarbonImmutable::parse('2026-02-01'),
        notes: '  Some notes with whitespace  ',
        howItHappened: '  Fell during workout  ',
        currentSymptoms: '  Sharp pain on movement  ',
    );

    expect($injury->notes)->toBe('Some notes with whitespace')
        ->and($injury->how_it_happened)->toBe('Fell during workout')
        ->and($injury->current_symptoms)->toBe('Sharp pain on movement');
});
