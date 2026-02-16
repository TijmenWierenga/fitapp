<?php

declare(strict_types=1);

namespace App\Support\Fit;

use App\Enums\Workout\Activity;

class SportMapper
{
    public static function fromActivity(Activity $activity): FitSportMapping
    {
        $specific = match ($activity) {
            Activity::Strength => new FitSportMapping(sport: 10, subSport: 20), // TRAINING / strength_training
            Activity::HIIT, Activity::Cardio => new FitSportMapping(sport: 10, subSport: 26), // TRAINING / cardio_training
            Activity::Yoga => new FitSportMapping(sport: 10, subSport: 43), // TRAINING / yoga
            Activity::Pilates => new FitSportMapping(sport: 10, subSport: 44), // TRAINING / pilates
            default => null,
        };

        if ($specific !== null) {
            return $specific;
        }

        return match ($activity->category()) {
            'running' => new FitSportMapping(sport: 1, subSport: 0), // RUNNING
            'cycling' => new FitSportMapping(sport: 2, subSport: 0), // CYCLING
            'swimming' => new FitSportMapping(sport: 5, subSport: 0), // SWIMMING
            'flexibility' => new FitSportMapping(sport: 10, subSport: 19), // TRAINING / flexibility_training
            default => new FitSportMapping(sport: 0, subSport: 0), // GENERIC
        };
    }
}
