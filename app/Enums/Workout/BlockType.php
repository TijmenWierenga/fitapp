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

    public function color(): string
    {
        return match ($this) {
            self::StraightSets => 'zinc',
            self::Circuit => 'amber',
            self::Superset => 'violet',
            self::Interval => 'blue',
            self::Amrap => 'red',
            self::ForTime => 'orange',
            self::Emom => 'cyan',
            self::DistanceDuration => 'green',
            self::Rest => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::StraightSets => 'bars-3',
            self::Circuit => 'arrow-path',
            self::Superset => 'arrows-right-left',
            self::Interval => 'clock',
            self::Amrap => 'fire',
            self::ForTime => 'bolt',
            self::Emom => 'clock',
            self::DistanceDuration => 'map-pin',
            self::Rest => 'pause',
        };
    }

    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return match ($this) {
            self::StraightSets, self::DistanceDuration, self::Rest => [],
            self::Circuit => ['rounds', 'rest_between_exercises', 'rest_between_rounds'],
            self::Superset => ['rounds', 'rest_between_rounds'],
            self::Interval => ['rounds', 'work_interval', 'rest_interval'],
            self::Amrap => ['time_cap'],
            self::ForTime => ['rounds', 'time_cap'],
            self::Emom => ['rounds', 'work_interval'],
        };
    }

    public function hasField(string $field): bool
    {
        return in_array($field, $this->fields(), true);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function fieldGuide(): array
    {
        $guide = [];

        foreach (self::cases() as $case) {
            $guide[$case->value] = $case->fields();
        }

        return $guide;
    }
}
