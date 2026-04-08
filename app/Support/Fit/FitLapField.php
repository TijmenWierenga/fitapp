<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitLapField: int
{
    case TotalElapsedTime = 7;
    case TotalDistance = 9;
    case AvgSpeed = 13;
    case AvgHeartRate = 15;
    case MaxHeartRate = 16;
    case AvgCadence = 17;
    case AvgPower = 19;
    case MaxPower = 20;
    case TotalAscent = 21;
    case Timestamp = 253;
}
