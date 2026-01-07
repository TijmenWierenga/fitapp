<?php

namespace App\ValueObjects;

/**
 * Handles conversion between human-readable time (minutes + seconds) and total seconds
 */
class TimeValue
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

    public static function fromSeconds(int $totalSeconds): self
    {
        $minutes = (int) floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        return new self($minutes, $seconds);
    }

    public function toSeconds(): int
    {
        return ($this->minutes * 60) + $this->seconds;
    }

    public function format(): string
    {
        return sprintf('%d:%02d', $this->minutes, $this->seconds);
    }

    public function isValid(): bool
    {
        $totalSeconds = $this->toSeconds();
        return $totalSeconds >= 10 && $totalSeconds <= 21600; // 10 seconds to 6 hours
    }
}
