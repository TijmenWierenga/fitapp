<?php

declare(strict_types=1);

namespace App\Enums;

enum FitnessGoal: string
{
    case WeightLoss = 'weight_loss';
    case MuscleGain = 'muscle_gain';
    case Endurance = 'endurance';
    case GeneralFitness = 'general_fitness';
    case SportsPerformance = 'sports_performance';
    case InjuryRecovery = 'injury_recovery';
    case Flexibility = 'flexibility';

    public function label(): string
    {
        return match ($this) {
            self::WeightLoss => 'Weight Loss',
            self::MuscleGain => 'Muscle Gain',
            self::Endurance => 'Endurance',
            self::GeneralFitness => 'General Fitness',
            self::SportsPerformance => 'Sports Performance',
            self::InjuryRecovery => 'Injury Recovery',
            self::Flexibility => 'Flexibility',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WeightLoss => 'Focus on burning calories and reducing body fat',
            self::MuscleGain => 'Build strength and increase muscle mass',
            self::Endurance => 'Improve cardiovascular fitness and stamina',
            self::GeneralFitness => 'Maintain overall health and well-being',
            self::SportsPerformance => 'Improve performance in a specific sport',
            self::InjuryRecovery => 'Rehabilitate and return to full activity',
            self::Flexibility => 'Improve mobility, flexibility, and movement quality',
        };
    }
}
