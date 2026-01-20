<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum Sport: string
{
    case Running = 'running';
    case Strength = 'strength';
    case Cardio = 'cardio';
    case Hiit = 'hiit';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'Running',
            self::Strength => 'Strength Training',
            self::Cardio => 'Cardio',
            self::Hiit => 'HIIT',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Running => 'bolt',
            self::Strength => 'fire',
            self::Cardio => 'heart',
            self::Hiit => 'bolt-slash',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Running => 'blue',
            self::Strength => 'orange',
            self::Cardio => 'red',
            self::Hiit => 'purple',
        };
    }

    public function hasStepBuilder(): bool
    {
        return match ($this) {
            self::Running => true,
            default => false,
        };
    }
}
