<?php

namespace App\Enums;

enum WorkoutType: string
{
    case Running = 'running';
    case Cycling = 'cycling';
    case StrengthTraining = 'strength_training';
    case Swimming = 'swimming';
    case Other = 'other';
}
