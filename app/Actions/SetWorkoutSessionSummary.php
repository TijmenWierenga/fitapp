<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\Enums\Workout\WorkoutSource;
use App\Models\Workout;

class SetWorkoutSessionSummary
{
    public function execute(Workout $workout, ParsedActivity $parsed): void
    {
        $workout->update([
            'total_duration' => $parsed->session->totalElapsedTime,
            'total_distance' => $parsed->session->totalDistance,
            'total_calories' => $parsed->session->totalCalories,
            'avg_heart_rate' => $parsed->session->avgHeartRate,
            'max_heart_rate' => $parsed->session->maxHeartRate,
            'source' => WorkoutSource::GarminFit,
        ]);
    }
}
