<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class GetWorkoutScheduleInput
{
    public function __construct(
        public int $upcomingDays,
        public int $completedDays,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            upcomingDays: min($data['upcoming_days'] ?? 14, 90),
            completedDays: min($data['completed_days'] ?? 7, 90),
        );
    }
}
