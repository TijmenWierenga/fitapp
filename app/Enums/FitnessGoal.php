<?php

declare(strict_types=1);

namespace App\Enums;

enum FitnessGoal: string
{
    case WeightLoss = 'weight_loss';
    case MuscleGain = 'muscle_gain';
    case Endurance = 'endurance';
    case GeneralFitness = 'general_fitness';

    public function label(): string
    {
        return match ($this) {
            self::WeightLoss => 'Weight Loss',
            self::MuscleGain => 'Muscle Gain',
            self::Endurance => 'Endurance',
            self::GeneralFitness => 'General Fitness',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WeightLoss => 'Focus on burning calories and reducing body fat',
            self::MuscleGain => 'Build strength and increase muscle mass',
            self::Endurance => 'Improve cardiovascular fitness and stamina',
            self::GeneralFitness => 'Maintain overall health and well-being',
        };
    }
}
