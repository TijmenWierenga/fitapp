<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Workout\Activity;
use Carbon\CarbonImmutable;

readonly class CreateWorkoutData
{
    public function __construct(
        public string $name,
        public Activity $activity,
        public CarbonImmutable $scheduledAt,
        public ?string $notes = null,
    ) {}
}
