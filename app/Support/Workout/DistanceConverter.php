<?php

namespace App\Support\Workout;

class DistanceConverter
{
    public static function toMeters(float $kilometers): int
    {
        return (int) round($kilometers * 1000);
    }

    public static function fromMeters(int $meters): float
    {
        return $meters / 1000;
    }

    public static function format(int $meters): string
    {
        if ($meters < 1000) {
            return $meters.' m';
        }

        return ($meters / 1000).' km';
    }
}
