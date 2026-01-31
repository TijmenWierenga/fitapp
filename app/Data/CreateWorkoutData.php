<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Workout\Activity;
use Illuminate\Support\Carbon;

readonly class CreateWorkoutData
{
    public function __construct(
        public string $name,
        public Activity $activity,
        public Carbon $scheduledAt,
        public ?string $notes = null,
    ) {}
}
