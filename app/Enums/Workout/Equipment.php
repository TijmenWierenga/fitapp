<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum Equipment: string
{
    case Barbell = 'barbell';
    case Dumbbell = 'dumbbell';
    case Kettlebell = 'kettlebell';
    case Bodyweight = 'bodyweight';
    case Machine = 'machine';
    case Cable = 'cable';
    case Band = 'band';
    case Other = 'other';
}
