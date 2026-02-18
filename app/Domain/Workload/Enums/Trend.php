<?php

declare(strict_types=1);

namespace App\Domain\Workload\Enums;

enum Trend: string
{
    case Increasing = 'increasing';
    case Stable = 'stable';
    case Decreasing = 'decreasing';
}
