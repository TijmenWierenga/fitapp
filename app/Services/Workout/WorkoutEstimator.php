<?php

namespace App\Services\Workout;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use App\Models\Step;
use App\Models\Workout;

class WorkoutEstimator
{
    private const DEFAULT_ACTIVE_PACE = 300; // 5:00 min/km

    private const DEFAULT_WARMUP_COOLDOWN_PACE = 420; // 7:00 min/km

    private const DEFAULT_REST_PACE = 540; // 9:00 min/km

    public function estimateDuration(Workout $workout): int
    {
        return (int) $workout->rootSteps->sum(function (Step $step) {
            return $this->calculateStepEstimatedDuration($step);
        });
    }

    public function estimateDistance(Workout $workout): int
    {
        return (int) $workout->rootSteps->sum(function (Step $step) {
            return $this->calculateStepEstimatedDistance($step);
        });
    }

    protected function calculateStepEstimatedDuration(Step $step): int
    {
        if ($step->step_kind === StepKind::Repeat) {
            $childDuration = $step->children->sum(function (Step $child) {
                return $this->calculateStepEstimatedDuration($child);
            });

            return (int) ($childDuration * $step->repeat_count);
        }

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

    protected function calculateStepEstimatedDistance(Step $step): int
    {
        if ($step->step_kind === StepKind::Repeat) {
            $childDistance = $step->children->sum(function (Step $child) {
                return $this->calculateStepEstimatedDistance($child);
            });

            return (int) ($childDistance * $step->repeat_count);
        }

        if ($step->duration_type === DurationType::Distance) {
            return $step->duration_value ?? 0;
        }

        if ($step->duration_type === DurationType::Time) {
            $durationInSeconds = $step->duration_value ?? 0;
            $pace = $this->getEstimatedPace($step);

            if ($pace <= 0) {
                return 0;
            }

            return (int) round(($durationInSeconds / $pace) * 1000);
        }

        return 0;
    }

    protected function getEstimatedPace(Step $step): int
    {
        // 1. Pace Target
        if ($step->target_type === TargetType::Pace) {
            if ($step->target_mode === TargetMode::Range && $step->target_low && $step->target_high) {
                return (int) (($step->target_low + $step->target_high) / 2);
            }

            // Note: If we had zone mappings, we could use them here for TargetMode::Zone
        }

        // 2. Intensity Fallback
        return match ($step->intensity) {
            Intensity::Active => self::DEFAULT_ACTIVE_PACE,
            Intensity::Warmup, Intensity::Cooldown => self::DEFAULT_WARMUP_COOLDOWN_PACE,
            Intensity::Rest => self::DEFAULT_REST_PACE,
            default => self::DEFAULT_ACTIVE_PACE,
        };
    }
}
