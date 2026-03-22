<?php

declare(strict_types=1);

namespace App\Domain\Workload\Enums;

enum AcwrZone: string
{
    case Undertraining = 'undertraining';
    case SweetSpot = 'sweet_spot';
    case Caution = 'caution';
    case Danger = 'danger';

    public static function fromAcwr(?float $acwr): self
    {
        if ($acwr === null) {
            return self::Undertraining;
        }

        return match (true) {
            $acwr < 0.8 => self::Undertraining,
            $acwr <= 1.3 => self::SweetSpot,
            $acwr <= 1.5 => self::Caution,
            default => self::Danger,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Undertraining => 'Undertraining',
            self::SweetSpot => 'Sweet Spot',
            self::Caution => 'Caution',
            self::Danger => 'Danger',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Undertraining => 'text-blue-600 dark:text-blue-400',
            self::SweetSpot => 'text-green-600 dark:text-green-400',
            self::Caution => 'text-amber-600 dark:text-amber-400',
            self::Danger => 'text-red-600 dark:text-red-400',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Undertraining => 'blue',
            self::SweetSpot => 'green',
            self::Caution => 'yellow',
            self::Danger => 'red',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Undertraining => 'Training load is low — safe to increase volume.',
            self::SweetSpot => 'Optimal training load — maintain current volume.',
            self::Caution => 'Elevated injury risk — consider reducing or holding volume.',
            self::Danger => 'High injury risk — significantly reduce volume or rest.',
        };
    }
}
