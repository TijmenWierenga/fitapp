<?php

declare(strict_types=1);

namespace App\Enums;

enum Side: string
{
    case Left = 'left';
    case Right = 'right';
    case Both = 'both';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Left => 'Left',
            self::Right => 'Right',
            self::Both => 'Both',
            self::NotApplicable => 'N/A',
        };
    }
}
