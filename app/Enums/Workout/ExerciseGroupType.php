<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum ExerciseGroupType: string
{
    case Straight = 'straight';
    case Superset = 'superset';
    case Circuit = 'circuit';
    case Emom = 'emom';
    case Amrap = 'amrap';

    public function label(): string
    {
        return match ($this) {
            self::Emom => 'EMOM',
            self::Amrap => 'AMRAP',
            default => ucfirst($this->value),
        };
    }
}
