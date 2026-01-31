<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Workout\Activity;
use Illuminate\Support\Carbon;

readonly class UpdateWorkoutData
{
    public function __construct(
        public ?string $name = null,
        public ?Activity $activity = null,
        public ?Carbon $scheduledAt = null,
        public ?string $notes = null,
        public bool $updateNotes = false,
    ) {}
}
