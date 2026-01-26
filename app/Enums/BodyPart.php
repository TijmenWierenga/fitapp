<?php

declare(strict_types=1);

namespace App\Enums;

enum BodyPart: string
{
    // Upper Body
    case Shoulder = 'shoulder';
    case Chest = 'chest';
    case Biceps = 'biceps';
    case Triceps = 'triceps';
    case Forearm = 'forearm';
    case Wrist = 'wrist';
    case Hand = 'hand';
    case Elbow = 'elbow';

    // Core/Spine
    case Neck = 'neck';
    case UpperBack = 'upper_back';
    case LowerBack = 'lower_back';
    case Core = 'core';
    case Ribs = 'ribs';

    // Lower Body
    case Hip = 'hip';
    case Glutes = 'glutes';
    case Quadriceps = 'quadriceps';
    case Hamstring = 'hamstring';
    case Knee = 'knee';
    case Calf = 'calf';
    case Ankle = 'ankle';
    case Foot = 'foot';

    // Other
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Shoulder => 'Shoulder',
            self::Chest => 'Chest',
            self::Biceps => 'Biceps',
            self::Triceps => 'Triceps',
            self::Forearm => 'Forearm',
            self::Wrist => 'Wrist',
            self::Hand => 'Hand',
            self::Elbow => 'Elbow',
            self::Neck => 'Neck',
            self::UpperBack => 'Upper Back',
            self::LowerBack => 'Lower Back',
            self::Core => 'Core',
            self::Ribs => 'Ribs',
            self::Hip => 'Hip',
            self::Glutes => 'Glutes',
            self::Quadriceps => 'Quadriceps',
            self::Hamstring => 'Hamstring',
            self::Knee => 'Knee',
            self::Calf => 'Calf',
            self::Ankle => 'Ankle',
            self::Foot => 'Foot',
            self::Other => 'Other',
        };
    }

    public function region(): string
    {
        return match ($this) {
            self::Shoulder, self::Chest, self::Biceps, self::Triceps,
            self::Forearm, self::Wrist, self::Hand, self::Elbow => 'Upper Body',
            self::Neck, self::UpperBack, self::LowerBack, self::Core, self::Ribs => 'Core & Spine',
            self::Hip, self::Glutes, self::Quadriceps, self::Hamstring,
            self::Knee, self::Calf, self::Ankle, self::Foot => 'Lower Body',
            self::Other => 'Other',
        };
    }

    /**
     * Get body parts grouped by region.
     *
     * @return array<string, array<self>>
     */
    public static function groupedByRegion(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->region()][] = $case;
        }

        return $grouped;
    }
}
