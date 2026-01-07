<?php

namespace App\Support\Workout;

class TimeConverter
{
    public static function toSeconds(int $minutes, int $seconds): int
    {
        return ($minutes * 60) + $seconds;
    }

    /**
     * @return array{minutes: int, seconds: int}
     */
    public static function fromSeconds(int $totalSeconds): array
    {
        return [
            'minutes' => (int) floor($totalSeconds / 60),
            'seconds' => $totalSeconds % 60,
        ];
    }

    public static function format(int $totalSeconds): string
    {
        $parts = self::fromSeconds($totalSeconds);

        return sprintf('%d:%02d', $parts['minutes'], $parts['seconds']);
    }
}
