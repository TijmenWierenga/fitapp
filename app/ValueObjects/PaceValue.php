<?php

namespace App\ValueObjects;

/**
 * Handles conversion between human-readable pace (minutes + seconds per km) and seconds_per_km
 */
class PaceValue
{
    public function __construct(
        public readonly int $minutes,
        public readonly int $seconds,
    ) {
        if ($seconds < 0 || $seconds > 59) {
            throw new \InvalidArgumentException('Seconds must be between 0 and 59');
        }
        if ($minutes < 0) {
            throw new \InvalidArgumentException('Minutes must be non-negative');
        }
    }

    public static function fromSecondsPerKm(int $secondsPerKm): self
    {
        $minutes = floor($secondsPerKm / 60);
        $seconds = $secondsPerKm % 60;

        return new self($minutes, $seconds);
    }

    public function toSecondsPerKm(): int
    {
        return ($this->minutes * 60) + $this->seconds;
    }

    public function format(): string
    {
        return sprintf('%d:%02d /km', $this->minutes, $this->seconds);
    }

    public function isValid(): bool
    {
        $secondsPerKm = $this->toSecondsPerKm();
        return $secondsPerKm >= 120 && $secondsPerKm <= 900; // 2:00 to 15:00 per km
    }
}
