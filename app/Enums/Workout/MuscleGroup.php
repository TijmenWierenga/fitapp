<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum MuscleGroup: string
{
    case Chest = 'chest';
    case UpperBack = 'upper_back';
    case Shoulders = 'shoulders';
    case Biceps = 'biceps';
    case Triceps = 'triceps';
    case Forearms = 'forearms';
    case Core = 'core';
    case LowerBack = 'lower_back';
    case Quadriceps = 'quadriceps';
    case Hamstrings = 'hamstrings';
    case Glutes = 'glutes';
    case Calves = 'calves';
    case HipFlexors = 'hip_flexors';
    case Cardiovascular = 'cardiovascular';

    public function label(): string
    {
        return match ($this) {
            self::UpperBack => 'Upper Back',
            self::LowerBack => 'Lower Back',
            self::HipFlexors => 'Hip Flexors',
            default => ucfirst($this->value),
        };
    }
}
