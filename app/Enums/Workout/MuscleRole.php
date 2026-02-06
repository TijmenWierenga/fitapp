<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum MuscleRole: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Stabilizer = 'stabilizer';
}
