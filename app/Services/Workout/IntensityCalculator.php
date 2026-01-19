<?php

namespace App\Services\Workout;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\IntensityLevel;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use App\Models\Step;
use App\Models\Workout;

class IntensityCalculator
{
    private const DEFAULT_SCORE = 50;

    /**
     * Calculate the intensity score for a workout (0-100).
     */
    public function calculate(Workout $workout): int
    {
        if ($workout->sport !== 'running') {
            return self::DEFAULT_SCORE;
        }

        $durationFactor = $this->calculateDurationFactor($workout);
        $intensityFactor = $this->calculateIntensityFactor($workout);
        $targetFactor = $this->calculateTargetFactor($workout);

        return min(100, (int) round($durationFactor + $intensityFactor + $targetFactor));
    }

    /**
     * Get the intensity level for a workout.
     */
    public function level(Workout $workout): IntensityLevel
    {
        return IntensityLevel::fromScore($this->calculate($workout));
    }

    /**
     * Calculate duration factor (0-40 points).
     * 100 minutes = max 40 points.
     */
    private function calculateDurationFactor(Workout $workout): float
    {
        $estimator = app(WorkoutEstimator::class);
        $durationSeconds = $estimator->estimateDuration($workout);
        $durationMinutes = $durationSeconds / 60;

        return min(40, $durationMinutes / 2.5);
    }

    /**
     * Calculate intensity factor (0-40 points).
     * Weighted by step intensity: Active=1.0, Warmup/Cooldown=0.3, Rest=0.1.
     */
    private function calculateIntensityFactor(Workout $workout): float
    {
        $totalWeightedDuration = 0;
        $totalDuration = 0;

        $this->processStepsForIntensity($workout->rootSteps, $totalWeightedDuration, $totalDuration);

        if ($totalDuration <= 0) {
            return 0;
        }

        $weightedRatio = $totalWeightedDuration / $totalDuration;

        return $weightedRatio * 40;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Step>  $steps
     */
    private function processStepsForIntensity(
        \Illuminate\Database\Eloquent\Collection $steps,
        float &$totalWeightedDuration,
        float &$totalDuration,
        int $multiplier = 1
    ): void {
        $estimator = app(WorkoutEstimator::class);

        foreach ($steps as $step) {
            if ($step->step_kind === StepKind::Repeat) {
                $this->processStepsForIntensity(
                    $step->children,
                    $totalWeightedDuration,
                    $totalDuration,
                    $multiplier * ($step->repeat_count ?? 1)
                );

                continue;
            }

            $stepDuration = $this->getStepDuration($step, $estimator);
            $effectiveDuration = $stepDuration * $multiplier;
            $weight = $this->getIntensityWeight($step->intensity);

            $totalWeightedDuration += $effectiveDuration * $weight;
            $totalDuration += $effectiveDuration;
        }
    }

    private function getStepDuration(Step $step, WorkoutEstimator $estimator): int
    {
        if ($step->duration_type === DurationType::Time) {
            return $step->duration_value ?? 0;
        }

        if ($step->duration_type === DurationType::Distance) {
            $distanceInMeters = $step->duration_value ?? 0;
            $pace = $this->getEstimatedPace($step);

            return (int) round(($distanceInMeters / 1000) * $pace);
        }

        return 0;
    }

    private function getEstimatedPace(Step $step): int
    {
        if ($step->target_type === TargetType::Pace) {
            if ($step->target_mode === TargetMode::Range && $step->target_low && $step->target_high) {
                return (int) (($step->target_low + $step->target_high) / 2);
            }
        }

        return match ($step->intensity) {
            Intensity::Active => 300,
            Intensity::Warmup, Intensity::Cooldown => 420,
            Intensity::Rest => 540,
            default => 300,
        };
    }

    private function getIntensityWeight(?Intensity $intensity): float
    {
        return match ($intensity) {
            Intensity::Active => 1.0,
            Intensity::Warmup, Intensity::Cooldown => 0.3,
            Intensity::Rest => 0.1,
            default => 0.5,
        };
    }

    /**
     * Calculate target factor (0-20 points).
     * Based on HR zone or pace in Active steps.
     */
    private function calculateTargetFactor(Workout $workout): float
    {
        $maxTargetScore = 0;
        $this->processStepsForTarget($workout->rootSteps, $maxTargetScore);

        return $maxTargetScore;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Step>  $steps
     */
    private function processStepsForTarget(
        \Illuminate\Database\Eloquent\Collection $steps,
        float &$maxTargetScore
    ): void {
        foreach ($steps as $step) {
            if ($step->step_kind === StepKind::Repeat) {
                $this->processStepsForTarget($step->children, $maxTargetScore);

                continue;
            }

            if ($step->intensity !== Intensity::Active) {
                continue;
            }

            $stepTargetScore = $this->getTargetScore($step);
            $maxTargetScore = max($maxTargetScore, $stepTargetScore);
        }
    }

    private function getTargetScore(Step $step): float
    {
        if ($step->target_type === TargetType::HeartRate) {
            return $this->getHeartRateTargetScore($step);
        }

        if ($step->target_type === TargetType::Pace) {
            return $this->getPaceTargetScore($step);
        }

        return 0;
    }

    private function getHeartRateTargetScore(Step $step): float
    {
        if ($step->target_mode === TargetMode::Zone && $step->target_zone !== null) {
            return match ($step->target_zone) {
                1, 2 => 0,
                3 => 5,
                4 => 10,
                5 => 20,
                default => 0,
            };
        }

        return 0;
    }

    private function getPaceTargetScore(Step $step): float
    {
        if ($step->target_mode === TargetMode::Range && $step->target_low && $step->target_high) {
            $avgPace = (int) (($step->target_low + $step->target_high) / 2);

            return match (true) {
                $avgPace > 360 => 0,
                $avgPace > 300 => 5,
                $avgPace > 270 => 10,
                default => 20,
            };
        }

        return 0;
    }
}
