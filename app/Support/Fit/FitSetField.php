<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitSetField: int
{
    case Duration = 0;
    case LegacyCategory = 1;
    case LegacyName = 2;
    case Repetitions = 3;
    case Weight = 4;
    case SetType = 5;
    case Category = 7;
    case Name = 8;
}
