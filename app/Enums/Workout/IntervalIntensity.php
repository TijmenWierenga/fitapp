<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum IntervalIntensity: string
{
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Threshold = 'threshold';
    case Tempo = 'tempo';
    case Vo2Max = 'vo2max';
    case Sprint = 'sprint';

    public function intensityMultiplier(): float
    {
        return match ($this) {
            self::Easy => 0.3,
            self::Moderate => 0.5,
            self::Threshold => 0.7,
            self::Tempo => 0.8,
            self::Vo2Max => 0.9,
            self::Sprint => 1.0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Vo2Max => 'VO2 Max',
            default => ucfirst($this->value),
        };
    }
}
