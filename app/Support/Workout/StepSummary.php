<?php

namespace App\Support\Workout;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use App\Models\Step;

class StepSummary
{
    /**
     * @param  Step|array  $step
     */
    public static function duration($step): string
    {
        $type = is_array($step) ? $step['duration_type'] : $step->duration_type;
        $value = is_array($step) ? $step['duration_value'] : $step->duration_value;

        if ($type instanceof DurationType) {
            $type = $type->value;
        }

        return match ($type) {
            DurationType::Time->value => TimeConverter::format($value),
            DurationType::Distance->value => DistanceConverter::format($value),
            DurationType::LapPress->value => 'Press Lap',
            default => '',
        };
    }

    /**
     * @param  Step|array  $step
     */
    public static function target($step): string
    {
        $type = is_array($step) ? $step['target_type'] : $step->target_type;
        $mode = is_array($step) ? $step['target_mode'] : $step->target_mode;
        $zone = is_array($step) ? $step['target_zone'] : $step->target_zone;
        $low = is_array($step) ? $step['target_low'] : $step->target_low;
        $high = is_array($step) ? $step['target_high'] : $step->target_high;

        if ($type instanceof TargetType) {
            $type = $type->value;
        }
        if ($mode instanceof TargetMode) {
            $mode = $mode->value;
        }

        return match ($type) {
            TargetType::None->value => 'No target',
            TargetType::HeartRate->value => match ($mode) {
                TargetMode::Zone->value => "Heart Rate Zone {$zone}",
                TargetMode::Range->value => "{$low}–{$high} BPM",
                default => 'Heart Rate target',
            },
            TargetType::Pace->value => match ($mode) {
                TargetMode::Zone->value => "Pace Zone {$zone}",
                TargetMode::Range->value => PaceConverter::formatRaw($low).'–'.PaceConverter::format($high),
                default => 'Pace target',
            },
            default => 'No target',
        };
    }
}
