<?php

namespace App\Enums;

enum DurationType: string
{
    case Time = 'time';
    case Distance = 'distance';
    case HrLessThan = 'hr_less_than';
    case HrGreaterThan = 'hr_greater_than';
    case Calories = 'calories';
    case Open = 'open';
    case RepetitionCount = 'repetition_count';
}
