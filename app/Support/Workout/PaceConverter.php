<?php

namespace App\Support\Workout;

class PaceConverter
{
    /**
     * Convert FIT enhanced speed (mm/s) to seconds per km.
     */
    public static function fromFitSpeed(int|float|null $fitSpeed): ?int
    {
        if ($fitSpeed === null || $fitSpeed <= 0) {
            return null;
        }

        return (int) round(1_000_000 / $fitSpeed);
    }

    public static function toSecondsPerKm(int $minutes, int $seconds): int
    {
        return ($minutes * 60) + $seconds;
    }

    /**
     * @return array{minutes: int, seconds: int}
     */
    public static function fromSecondsPerKm(int $secondsPerKm): array
    {
        return [
            'minutes' => (int) floor($secondsPerKm / 60),
            'seconds' => $secondsPerKm % 60,
        ];
    }

    public static function format(int $secondsPerKm): string
    {
        return self::formatRaw($secondsPerKm).' /km';
    }

    public static function formatRaw(int $secondsPerKm): string
    {
        $parts = self::fromSecondsPerKm($secondsPerKm);

        return sprintf('%d:%02d', $parts['minutes'], $parts['seconds']);
    }
}
