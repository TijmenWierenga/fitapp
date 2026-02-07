<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum ExerciseCategory: string
{
    case Compound = 'compound';
    case Isolation = 'isolation';
    case Cardio = 'cardio';
    case Mobility = 'mobility';
}
