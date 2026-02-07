<?php

declare(strict_types=1);

namespace App\Support\Workout;

class WorkoutDisplayFormatter
{
    public static function setsReps(?int $sets, ?int $repsMin, ?int $repsMax): ?string
    {
        if ($sets === null && $repsMin === null && $repsMax === null) {
            return null;
        }

        $repsText = null;

        if ($repsMax !== null) {
            $repsText = ($repsMin !== null && $repsMin !== $repsMax)
                ? "{$repsMin}-{$repsMax} reps"
                : "{$repsMax} reps";
        }

        if ($sets !== null && $repsText !== null) {
            return "{$sets} sets of {$repsText}";
        }

        if ($sets !== null) {
            return "{$sets} sets";
        }

        return $repsText;
    }

    public static function weight(float|string|null $weight): ?string
    {
        if ($weight === null) {
            return null;
        }

        $value = (float) $weight;

        if ($value <= 0) {
            return null;
        }

        $formatted = (floor($value) == $value) ? (int) $value : $value;

        return "{$formatted} kg";
    }

    public static function duration(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }

        return TimeConverter::format($seconds);
    }

    public static function distance(?int $meters): ?string
    {
        if ($meters === null || $meters <= 0) {
            return null;
        }

        return DistanceConverter::format($meters);
    }

    public static function paceRange(?int $min, ?int $max): ?string
    {
        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $max !== null && $min !== $max) {
            return PaceConverter::formatRaw($min).'-'.PaceConverter::formatRaw($max).' /km';
        }

        $value = $min ?? $max;

        return PaceConverter::format($value);
    }

    public static function hrZone(?int $zone): ?string
    {
        if ($zone === null) {
            return null;
        }

        return "Zone {$zone}";
    }

    public static function hrRange(?int $min, ?int $max): ?string
    {
        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $max !== null) {
            return "{$min}-{$max} bpm";
        }

        $value = $min ?? $max;

        return "{$value} bpm";
    }

    public static function power(?int $watts): ?string
    {
        if ($watts === null || $watts <= 0) {
            return null;
        }

        return "{$watts} W";
    }

    public static function intervals(?int $work, ?int $rest): ?string
    {
        if ($work === null && $rest === null) {
            return null;
        }

        $parts = [];

        if ($work !== null) {
            $parts[] = TimeConverter::format($work).' on';
        }

        if ($rest !== null) {
            $parts[] = TimeConverter::format($rest).' off';
        }

        return implode(' / ', $parts);
    }

    public static function rpe(string|float|null $rpe): ?string
    {
        if ($rpe === null) {
            return null;
        }

        $value = (float) $rpe;

        if ($value <= 0) {
            return null;
        }

        $formatted = (floor($value) == $value) ? (int) $value : $value;
        $label = self::rpeLabel($value);

        return "RPE {$formatted} ({$label})";
    }

    public static function rpeLabel(float $rpe): string
    {
        return match (true) {
            $rpe <= 2 => 'Very Easy',
            $rpe <= 4 => 'Easy',
            $rpe <= 6 => 'Moderate',
            $rpe <= 8 => 'Hard',
            default => 'Maximum Effort',
        };
    }

    public static function rest(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }

        return TimeConverter::format($seconds);
    }
}
