<?php

namespace App\Support\Workout;

class TimeConverter
{
    public static function toSeconds(int $minutes, int $seconds): int
    {
        return ($minutes * 60) + $seconds;
    }

    /**
     * @return array{hours: int, minutes: int, seconds: int}
     */
    public static function fromSeconds(int $totalSeconds): array
    {
        return [
            'hours' => (int) floor($totalSeconds / 3600),
            'minutes' => (int) floor(($totalSeconds % 3600) / 60),
            'seconds' => $totalSeconds % 60,
        ];
    }

    public static function format(int $totalSeconds): string
    {
        $parts = self::fromSeconds($totalSeconds);
        $result = [];

        if ($parts['hours'] > 0) {
            $result[] = $parts['hours'].'h';
        }

        if ($parts['minutes'] > 0) {
            $result[] = $parts['minutes'].'min';
        }

        if ($parts['seconds'] > 0 || empty($result)) {
            $result[] = $parts['seconds'].'s';
        }

        return implode(' ', $result);
    }
}
