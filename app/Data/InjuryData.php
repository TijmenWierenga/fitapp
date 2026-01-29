<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Illuminate\Support\Carbon;

readonly class InjuryData
{
    public function __construct(
        public InjuryType $injuryType,
        public BodyPart $bodyPart,
        public Carbon $startedAt,
        public ?Carbon $endedAt = null,
        public ?string $notes = null,
    ) {}
}
