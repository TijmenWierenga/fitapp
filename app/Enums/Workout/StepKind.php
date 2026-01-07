<?php

namespace App\Enums\Workout;

enum StepKind: string
{
    case Warmup = 'warmup';
    case Run = 'run';
    case Recovery = 'recovery';
    case Cooldown = 'cooldown';
    case Repeat = 'repeat';
}
