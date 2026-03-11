<?php

declare(strict_types=1);

namespace App\Enums;

enum ExperienceLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Beginner => 'Less than 1 year consistent training',
            self::Intermediate => '1-3 years consistent training',
            self::Advanced => '3+ years consistent training',
        };
    }
}
