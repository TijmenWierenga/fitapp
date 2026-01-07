<?php

namespace App\Support\Workout;

class DistanceConverter
{
    public static function toMeters(int $kilometers, int $tensOfMeters): int
    {
        return ($kilometers * 1000) + ($tensOfMeters * 10);
    }

    /**
     * @return array{kilometers: int, tens_of_meters: int}
     */
    public static function fromMeters(int $meters): array
    {
        return [
            'kilometers' => (int) floor($meters / 1000),
            'tens_of_meters' => (int) (($meters % 1000) / 10),
        ];
    }

    public static function format(int $meters): string
    {
        return sprintf('%.3f km', $meters / 1000);
    }
}
