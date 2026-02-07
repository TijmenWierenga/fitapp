<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum ExerciseType: string
{
    case Strength = 'strength';
    case Cardio = 'cardio';
    case Duration = 'duration';
}
