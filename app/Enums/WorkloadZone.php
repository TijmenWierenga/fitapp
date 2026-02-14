<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkloadZone: string
{
    case Inactive = 'inactive';
    case Undertraining = 'undertraining';
    case SweetSpot = 'sweet_spot';
    case Caution = 'caution';
    case Danger = 'danger';

    public static function fromAcwr(float $acwr): self
    {
        if ($acwr === 0.0) {
            return self::Inactive;
        }

        return match (true) {
            $acwr < 0.8 => self::Undertraining,
            $acwr <= 1.3 => self::SweetSpot,
            $acwr <= 1.5 => self::Caution,
            default => self::Danger,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Inactive, self::Undertraining => 'gray',
            self::SweetSpot => 'green',
            self::Caution => 'yellow',
            self::Danger => 'red',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Inactive => 'Inactive',
            self::Undertraining => 'Undertraining',
            self::SweetSpot => 'Sweet Spot',
            self::Caution => 'Caution',
            self::Danger => 'Danger',
        };
    }
}
