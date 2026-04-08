<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitExerciseTitleField: int
{
    case Category = 0;
    case Name = 1;
    case DisplayName = 2;
}
