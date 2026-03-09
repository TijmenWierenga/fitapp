<?php

declare(strict_types=1);

namespace App\Enums;

enum Equipment: string
{
    case Bands = 'bands';
    case Barbell = 'barbell';
    case BodyOnly = 'body only';
    case Cable = 'cable';
    case Dumbbell = 'dumbbell';
    case EZCurlBar = 'e-z curl bar';
    case ExerciseBall = 'exercise ball';
    case FoamRoll = 'foam roll';
    case Kettlebells = 'kettlebells';
    case Machine = 'machine';
    case MedicineBall = 'medicine ball';
    case Other = 'other';

    /**
     * @return array<self>
     */
    public static function homeEquipmentOptions(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case): bool => ! in_array($case, [self::BodyOnly, self::Other], true),
        ));
    }

    public function label(): string
    {
        return match ($this) {
            self::Bands => 'Resistance Bands',
            self::Barbell => 'Barbell',
            self::BodyOnly => 'Body Only',
            self::Cable => 'Cable',
            self::Dumbbell => 'Dumbbell',
            self::EZCurlBar => 'EZ Curl Bar',
            self::ExerciseBall => 'Exercise Ball',
            self::FoamRoll => 'Foam Roll',
            self::Kettlebells => 'Kettlebells',
            self::Machine => 'Machine',
            self::MedicineBall => 'Medicine Ball',
            self::Other => 'Other',
        };
    }
}
