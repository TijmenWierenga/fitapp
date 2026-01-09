<?php

namespace App\Enums\Workout;

enum Intensity: string
{
    case Warmup = 'warmup';
    case Active = 'active';
    case Rest = 'rest';
    case Cooldown = 'cooldown';
}
