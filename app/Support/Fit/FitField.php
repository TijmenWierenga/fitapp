<?php

declare(strict_types=1);

namespace App\Support\Fit;

class FitField
{
    public function __construct(
        public readonly int $fieldNumber,
        public readonly FitBaseType $baseType,
        public readonly int|string|null $value,
        public readonly ?int $size = null,
    ) {}

    public static function decode(int $fieldNumber, FitBaseType $baseType, string $bytes, bool $bigEndian = false): self
    {
        $value = self::decodeValue($baseType, $bytes, $bigEndian);

        return new self($fieldNumber, $baseType, $value, strlen($bytes));
    }

    private static function decodeValue(FitBaseType $baseType, string $bytes, bool $bigEndian): int|float|string|null
    {
        $value = match ($baseType) {
            FitBaseType::Enum, FitBaseType::UInt8, FitBaseType::UInt8z => unpack('C', $bytes)[1],
            FitBaseType::SInt8 => unpack('c', $bytes)[1],
            FitBaseType::UInt16, FitBaseType::UInt16z => $bigEndian ? unpack('n', $bytes)[1] : unpack('v', $bytes)[1],
            FitBaseType::SInt16 => self::decodeSInt16($bytes, $bigEndian),
            FitBaseType::UInt32, FitBaseType::UInt32z => $bigEndian ? unpack('N', $bytes)[1] : unpack('V', $bytes)[1],
            FitBaseType::SInt32 => self::decodeSInt32($bytes, $bigEndian),
            FitBaseType::Float32 => $bigEndian ? unpack('G', $bytes)[1] : unpack('g', $bytes)[1],
            FitBaseType::Float64 => $bigEndian ? unpack('E', $bytes)[1] : unpack('e', $bytes)[1],
            FitBaseType::String => rtrim($bytes, "\x00"),
        };

        if ($baseType === FitBaseType::String) {
            return $value === '' ? null : $value;
        }

        if ($baseType === FitBaseType::Float32 || $baseType === FitBaseType::Float64) {
            return is_nan($value) ? null : $value;
        }

        return $value === $baseType->invalidValue() ? null : $value;
    }

    private static function decodeSInt16(string $bytes, bool $bigEndian): int
    {
        $unsigned = $bigEndian ? unpack('n', $bytes)[1] : unpack('v', $bytes)[1];

        return $unsigned >= 0x8000 ? $unsigned - 0x10000 : $unsigned;
    }

    private static function decodeSInt32(string $bytes, bool $bigEndian): int
    {
        $unsigned = $bigEndian ? unpack('N', $bytes)[1] : unpack('V', $bytes)[1];

        return $unsigned >= 0x80000000 ? $unsigned - 0x100000000 : $unsigned;
    }

    public function fieldSize(): int
    {
        if ($this->baseType === FitBaseType::String) {
            return $this->size ?? (is_string($this->value) ? strlen($this->value) + 1 : 1);
        }

        return $this->baseType->size();
    }

    public function encode(): string
    {
        if ($this->value === null) {
            return $this->encodeInvalid();
        }

        return match ($this->baseType) {
            FitBaseType::Enum, FitBaseType::UInt8, FitBaseType::UInt8z => pack('C', $this->value),
            FitBaseType::SInt8 => pack('c', $this->value),
            FitBaseType::UInt16, FitBaseType::UInt16z => pack('v', $this->value),
            FitBaseType::SInt16 => pack('v', $this->value & 0xFFFF),
            FitBaseType::UInt32, FitBaseType::UInt32z => pack('V', $this->value),
            FitBaseType::SInt32 => pack('V', $this->value & 0xFFFFFFFF),
            FitBaseType::Float32 => pack('g', $this->value),
            FitBaseType::Float64 => pack('e', $this->value),
            FitBaseType::String => $this->encodeString(),
        };
    }

    private function encodeInvalid(): string
    {
        if ($this->baseType === FitBaseType::String) {
            return str_repeat("\x00", $this->fieldSize());
        }

        return match ($this->baseType) {
            FitBaseType::Enum, FitBaseType::UInt8, FitBaseType::UInt8z => pack('C', $this->baseType->invalidValue()),
            FitBaseType::SInt8 => pack('c', $this->baseType->invalidValue()),
            FitBaseType::UInt16, FitBaseType::UInt16z => pack('v', $this->baseType->invalidValue()),
            FitBaseType::SInt16 => pack('v', $this->baseType->invalidValue()),
            FitBaseType::UInt32, FitBaseType::UInt32z, FitBaseType::SInt32 => pack('V', $this->baseType->invalidValue()),
            FitBaseType::Float32 => pack('g', NAN),
            FitBaseType::Float64 => pack('e', NAN),
            default => '',
        };
    }

    private function encodeString(): string
    {
        $str = is_string($this->value) ? $this->value : '';
        $fieldSize = $this->fieldSize();

        $str = substr($str, 0, $fieldSize - 1);

        return str_pad($str."\x00", $fieldSize, "\x00");
    }
}
