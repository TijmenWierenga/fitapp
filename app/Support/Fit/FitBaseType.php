<?php

declare(strict_types=1);

namespace App\Support\Fit;

enum FitBaseType: int
{
    case Enum = 0x00;
    case SInt8 = 0x01;
    case UInt8 = 0x02;
    case SInt16 = 0x83;
    case UInt16 = 0x84;
    case SInt32 = 0x85;
    case UInt32 = 0x86;
    case String = 0x07;
    case Float32 = 0x88;
    case Float64 = 0x89;
    case UInt8z = 0x0A;
    case UInt16z = 0x0B;
    case UInt32z = 0x0C;

    public function size(): int
    {
        return match ($this) {
            self::Enum, self::SInt8, self::UInt8, self::UInt8z, self::String => 1,
            self::SInt16, self::UInt16, self::UInt16z => 2,
            self::SInt32, self::UInt32, self::UInt32z, self::Float32 => 4,
            self::Float64 => 8,
        };
    }

    public function invalidValue(): int|float
    {
        return match ($this) {
            self::Enum, self::UInt8 => 0xFF,
            self::SInt8 => 0x7F,
            self::UInt16 => 0xFFFF,
            self::SInt16 => 0x7FFF,
            self::UInt32 => 0xFFFFFFFF,
            self::SInt32 => 0x7FFFFFFF,
            self::String => 0x00,
            self::Float32 => NAN,
            self::Float64 => NAN,
            self::UInt8z => 0x00,
            self::UInt16z => 0x0000,
            self::UInt32z => 0x00000000,
        };
    }
}
