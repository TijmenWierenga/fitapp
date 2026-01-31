<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FitnessGoal;

readonly class FitnessProfileData
{
    public function __construct(
        public FitnessGoal $primaryGoal,
        public int $availableDaysPerWeek,
        public int $minutesPerSession,
        public ?string $goalDetails = null,
    ) {}
}
