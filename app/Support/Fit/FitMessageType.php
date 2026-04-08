<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitMessageType: int
{
    case FileId = 0;
    case Session = 18;
    case Lap = 19;
    case Record = 20;
    case Set = 225;
    case ExerciseTitle = 264;
}
