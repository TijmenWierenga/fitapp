<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitBaseType: int
{
    case Enum = 0x00;
    case UInt8 = 0x02;
    case UInt16 = 0x84;
    case UInt32 = 0x86;
    case SInt32 = 0x85;
    case String = 0x07;

    public function size(): int
    {
        return match ($this) {
            self::Enum, self::UInt8, self::String => 1,
            self::UInt16 => 2,
            self::UInt32, self::SInt32 => 4,
        };
    }

    public function invalidValue(): int
    {
        return match ($this) {
            self::Enum, self::UInt8 => 0xFF,
            self::UInt16 => 0xFFFF,
            self::UInt32 => 0xFFFFFFFF,
            self::SInt32 => 0x7FFFFFFF,
            self::String => 0x00,
        };
    }
}
