<?php

declare(strict_types=1);

namespace App\Enums;

enum BiologicalSex: string
{
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
        };
    }
}
