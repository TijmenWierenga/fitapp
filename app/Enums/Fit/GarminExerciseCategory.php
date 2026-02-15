<?php

declare(strict_types=1);

namespace App\Enums\Fit;

enum GarminExerciseCategory: int
{
    case BenchPress = 0;
    case CalfRaise = 1;
    case Cardio = 2;
    case Carry = 3;
    case Chop = 4;
    case Core = 5;
    case Crunch = 6;
    case Curl = 7;
    case Deadlift = 8;
    case Flye = 9;
    case HipRaise = 10;
    case HipStability = 11;
    case HipSwing = 12;
    case Hyperextension = 13;
    case LateralRaise = 14;
    case LegCurl = 15;
    case LegRaise = 16;
    case Lunge = 17;
    case OlympicLift = 18;
    case Plank = 19;
    case Plyo = 20;
    case PullUp = 21;
    case PushUp = 22;
    case Row = 23;
    case ShoulderPress = 24;
    case ShoulderStability = 25;
    case Shrug = 26;
    case SitUp = 27;
    case Squat = 28;
    case TotalBody = 29;
    case TricepsExtension = 30;
    case WarmUp = 31;
    case Run = 32;
    case Unknown = 65534;

    public function label(): string
    {
        return match ($this) {
            self::BenchPress => 'Bench Press',
            self::CalfRaise => 'Calf Raise',
            self::Cardio => 'Cardio',
            self::Carry => 'Carry',
            self::Chop => 'Chop',
            self::Core => 'Core',
            self::Crunch => 'Crunch',
            self::Curl => 'Curl',
            self::Deadlift => 'Deadlift',
            self::Flye => 'Flye',
            self::HipRaise => 'Hip Raise',
            self::HipStability => 'Hip Stability',
            self::HipSwing => 'Hip Swing',
            self::Hyperextension => 'Hyperextension',
            self::LateralRaise => 'Lateral Raise',
            self::LegCurl => 'Leg Curl',
            self::LegRaise => 'Leg Raise',
            self::Lunge => 'Lunge',
            self::OlympicLift => 'Olympic Lift',
            self::Plank => 'Plank',
            self::Plyo => 'Plyo',
            self::PullUp => 'Pull-Up',
            self::PushUp => 'Push-Up',
            self::Row => 'Row',
            self::ShoulderPress => 'Shoulder Press',
            self::ShoulderStability => 'Shoulder Stability',
            self::Shrug => 'Shrug',
            self::SitUp => 'Sit-Up',
            self::Squat => 'Squat',
            self::TotalBody => 'Total Body',
            self::TricepsExtension => 'Triceps Extension',
            self::WarmUp => 'Warm Up',
            self::Run => 'Run',
            self::Unknown => 'Unknown',
        };
    }
}
