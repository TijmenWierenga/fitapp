<?php

namespace App\Enums;

enum TargetType: string
{
    case Speed = 'speed';
    case HeartRate = 'heart_rate';
    case Open = 'open';
    case Cadence = 'cadence';
    case Power = 'power';
    case Pace = 'pace';
}
