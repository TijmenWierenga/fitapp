<?php

namespace App\Actions;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Models\Injury;
use App\Models\User;
use Carbon\CarbonImmutable;

class CreateInjury
{
    public function execute(
        User $user,
        InjuryType $injuryType,
        BodyPart $bodyPart,
        CarbonImmutable $startedAt,
        ?Severity $severity = null,
        ?Side $side = null,
        ?CarbonImmutable $endedAt = null,
        ?string $notes = null,
        ?string $howItHappened = null,
        ?string $currentSymptoms = null,
    ): Injury {
        return $user->injuries()->create([
            'injury_type' => $injuryType,
            'body_part' => $bodyPart,
            'severity' => $severity,
            'side' => $side,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => $notes,
            'how_it_happened' => $howItHappened,
            'current_symptoms' => $currentSymptoms,
        ]);
    }
}
