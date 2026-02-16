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
            FitBaseType::Enum, FitBaseType::UInt8 => pack('C', $this->value),
            FitBaseType::UInt16 => pack('v', $this->value),
            FitBaseType::UInt32 => pack('V', $this->value),
            FitBaseType::SInt32 => pack('V', $this->value & 0xFFFFFFFF),
            FitBaseType::String => $this->encodeString(),
        };
    }

    private function encodeInvalid(): string
    {
        if ($this->baseType === FitBaseType::String) {
            return str_repeat("\x00", $this->fieldSize());
        }

        return match ($this->baseType) {
            FitBaseType::Enum, FitBaseType::UInt8 => pack('C', $this->baseType->invalidValue()),
            FitBaseType::UInt16 => pack('v', $this->baseType->invalidValue()),
            FitBaseType::UInt32, FitBaseType::SInt32 => pack('V', $this->baseType->invalidValue()),
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
