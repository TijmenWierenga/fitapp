<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Carbon\CarbonImmutable;

readonly class InjuryData
{
    public function __construct(
        public InjuryType $injuryType,
        public BodyPart $bodyPart,
        public CarbonImmutable $startedAt,
        public ?CarbonImmutable $endedAt = null,
        public ?string $notes = null,
    ) {}
}
