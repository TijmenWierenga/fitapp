<?php

declare(strict_types=1);

namespace App\Data;

readonly class InjuryEvaluationData
{
    public function __construct(
        public int $injuryId,
        public ?int $discomfortScore = null,
        public ?string $notes = null,
    ) {}
}
