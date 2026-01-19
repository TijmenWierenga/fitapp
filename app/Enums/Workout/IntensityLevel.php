<?php

namespace App\Enums\Workout;

enum IntensityLevel: string
{
    case Recovery = 'recovery';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case VeryHigh = 'very_high';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score <= 25 => self::Recovery,
            $score <= 45 => self::Low,
            $score <= 65 => self::Medium,
            $score <= 85 => self::High,
            default => self::VeryHigh,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Recovery => 'Recovery',
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::VeryHigh => 'Very High',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Recovery => 'bg-slate-100 text-slate-600 dark:bg-slate-800/30 dark:text-slate-400',
            self::Low => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
            self::Medium => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            self::High => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            self::VeryHigh => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-400',
        };
    }

    public function borderColorClasses(): string
    {
        return match ($this) {
            self::Recovery => 'border-slate-300 dark:border-slate-700',
            self::Low => 'border-teal-300 dark:border-teal-700',
            self::Medium => 'border-amber-300 dark:border-amber-700',
            self::High => 'border-orange-300 dark:border-orange-700',
            self::VeryHigh => 'border-fuchsia-300 dark:border-fuchsia-700',
        };
    }

    public function hoverRingClasses(): string
    {
        return match ($this) {
            self::Recovery => 'hover:ring-slate-500',
            self::Low => 'hover:ring-teal-500',
            self::Medium => 'hover:ring-amber-500',
            self::High => 'hover:ring-orange-500',
            self::VeryHigh => 'hover:ring-fuchsia-500',
        };
    }
}
