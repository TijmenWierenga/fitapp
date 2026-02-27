<?php

declare(strict_types=1);

namespace App\Enums;

enum Severity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';

    public function label(): string
    {
        return match ($this) {
            self::Mild => 'Mild',
            self::Moderate => 'Moderate',
            self::Severe => 'Severe',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Mild => 'lime',
            self::Moderate => 'amber',
            self::Severe => 'red',
        };
    }
}
