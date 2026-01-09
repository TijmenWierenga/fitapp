<?php

namespace App\Enums\Workout;

enum DurationType: string
{
    case Time = 'time';
    case Distance = 'distance';
    case LapPress = 'lap_press';
}
