<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum BlockType: string
{
    case StraightSets = 'straight_sets';
    case Circuit = 'circuit';
    case Superset = 'superset';
    case Interval = 'interval';
    case Amrap = 'amrap';
    case ForTime = 'for_time';
    case Emom = 'emom';
    case DistanceDuration = 'distance_duration';
    case Rest = 'rest';

    public function label(): string
    {
        return match ($this) {
            self::StraightSets => 'Straight Sets',
            self::Circuit => 'Circuit',
            self::Superset => 'Superset',
            self::Interval => 'Interval',
            self::Amrap => 'AMRAP',
            self::ForTime => 'For Time',
            self::Emom => 'EMOM',
            self::DistanceDuration => 'Distance/Duration',
            self::Rest => 'Rest',
        };
    }
}
