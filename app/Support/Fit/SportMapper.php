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

    public static function toActivity(int $sport, int $subSport): Activity
    {
        return match (true) {
            // Running sub-sports
            $sport === 1 && $subSport === 1 => Activity::Treadmill,
            $sport === 1 && $subSport === 14 => Activity::TrailRun,
            $sport === 1 && $subSport === 45 => Activity::UltraRun,
            $sport === 1 => Activity::Run,

            // Cycling sub-sports
            $sport === 2 && $subSport === 6 => Activity::BikeIndoor,
            $sport === 2 && $subSport === 8 => Activity::MountainBike,
            $sport === 2 && $subSport === 7 => Activity::RoadBike,
            $sport === 2 && $subSport === 11 => Activity::GravelBike,
            $sport === 2 && $subSport === 28 => Activity::EBike,
            $sport === 2 => Activity::Bike,

            // Swimming
            $sport === 5 && $subSport === 17 => Activity::PoolSwim,
            $sport === 5 && $subSport === 18 => Activity::OpenWater,
            $sport === 5 => Activity::PoolSwim,

            // Training sub-sports
            $sport === 10 && $subSport === 20 => Activity::Strength,
            $sport === 10 && $subSport === 26 => Activity::HIIT,
            $sport === 10 && $subSport === 43 => Activity::Yoga,
            $sport === 10 && $subSport === 44 => Activity::Pilates,
            $sport === 10 && $subSport === 19 => Activity::Mobility,
            $sport === 10 => Activity::Cardio,

            // Walking
            $sport === 11 => Activity::Walk,

            // Hiking
            $sport === 17 => Activity::Hike,

            // Rowing
            $sport === 15 => Activity::Row,

            // Elliptical
            $sport === 4 => Activity::Elliptical,

            default => Activity::Other,
        };
    }
}
