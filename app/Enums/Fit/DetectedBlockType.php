<?php

declare(strict_types=1);

namespace App\Enums\Fit;

enum DetectedBlockType: string
{
    case Superset = 'superset';
    case Straight = 'straight';
}
