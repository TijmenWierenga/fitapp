<?php

namespace App\Enums;

enum Intensity: string
{
    case Warmup = 'warmup';
    case Active = 'active';
    case Rest = 'rest';
    case Cooldown = 'cooldown';
    case Recovery = 'recovery';
    case Interval = 'interval';
    case Other = 'other';
}
