<?php

namespace App\ValueObjects;

/**
 * Handles conversion between human-readable distance (km + tens_of_meters) and meters
 */
class DistanceValue
{
    public function __construct(
        public readonly int $kilometers,
        public readonly int $tensOfMeters,
    ) {
        if ($tensOfMeters < 0 || $tensOfMeters > 99) {
            throw new \InvalidArgumentException('Tens of meters must be between 0 and 99');
        }
        if ($kilometers < 0) {
            throw new \InvalidArgumentException('Kilometers must be non-negative');
        }
    }

    public static function fromMeters(int $meters): self
    {
        if ($meters % 10 !== 0) {
            throw new \InvalidArgumentException('Meters must be divisible by 10');
        }

        $kilometers = (int) floor($meters / 1000);
        $tensOfMeters = (int) (($meters % 1000) / 10);

        return new self($kilometers, $tensOfMeters);
    }

    public function toMeters(): int
    {
        return ($this->kilometers * 1000) + ($this->tensOfMeters * 10);
    }

    public function format(): string
    {
        $meters = $this->toMeters();
        return sprintf('%.3f km', $meters / 1000);
    }

    public function isValid(): bool
    {
        $meters = $this->toMeters();
        return $meters >= 10 && $meters <= 100000; // 10 meters to 100 km
    }
}
