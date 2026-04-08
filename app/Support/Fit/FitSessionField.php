<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitSessionField: int
{
    case Sport = 5;
    case SubSport = 6;
    case TotalElapsedTime = 7;
    case TotalDistance = 9;
    case TotalCalories = 11;
    case AvgHeartRate = 16;
    case MaxHeartRate = 17;
    case AvgPower = 20;
    case WorkoutName = 29;
    case Timestamp = 253;
}
