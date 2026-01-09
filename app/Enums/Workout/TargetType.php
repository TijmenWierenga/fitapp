<?php

namespace App\Enums\Workout;

enum TargetType: string
{
    case None = 'none';
    case HeartRate = 'heart_rate';
    case Pace = 'pace';
}
