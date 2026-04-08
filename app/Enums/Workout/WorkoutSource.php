<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum WorkoutSource: string
{
    case GarminFit = 'garmin_fit';

    public function label(): string
    {
        return match ($this) {
            self::GarminFit => 'Garmin FIT',
        };
    }
}
