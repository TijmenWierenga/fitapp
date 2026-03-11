<?php

declare(strict_types=1);

namespace App\Enums;

enum Equipment: string
{
    case Bands = 'bands';
    case Barbell = 'barbell';
    case Box = 'box';
    case Cable = 'cable';
    case DipStation = 'dip station';
    case Dumbbell = 'dumbbell';
    case EZCurlBar = 'e-z curl bar';
    case ExerciseBall = 'exercise ball';
    case FoamRoll = 'foam roll';
    case Kettlebells = 'kettlebells';
    case Machine = 'machine';
    case MedicineBall = 'medicine ball';
    case Other = 'other';
    case PullUpBar = 'pull-up bar';
    case Rings = 'rings';
    case Sled = 'sled';
    case SuspensionTrainer = 'suspension trainer';
    case TrapBar = 'trap bar';
    case WeightPlate = 'weight plate';

    /**
     * @return array<self>
     */
    public static function homeEquipmentOptions(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case): bool => ! in_array($case, [self::Other, self::Sled, self::Machine, self::Cable], true),
        ));
    }

    public function label(): string
    {
        return match ($this) {
            self::Bands => 'Resistance Bands',
            self::Barbell => 'Barbell',
            self::Box => 'Plyo Box',
            self::Cable => 'Cable',
            self::DipStation => 'Dip/Parallel Bars',
            self::Dumbbell => 'Dumbbell',
            self::EZCurlBar => 'EZ Curl Bar',
            self::ExerciseBall => 'Exercise Ball',
            self::FoamRoll => 'Foam Roll',
            self::Kettlebells => 'Kettlebells',
            self::Machine => 'Machine',
            self::MedicineBall => 'Medicine Ball',
            self::Other => 'Other',
            self::PullUpBar => 'Pull-Up Bar',
            self::Rings => 'Gymnastic Rings',
            self::Sled => 'Sled',
            self::SuspensionTrainer => 'Suspension Trainer',
            self::TrapBar => 'Trap Bar',
            self::WeightPlate => 'Weight Plate',
        };
    }
}
