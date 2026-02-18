<?php

declare(strict_types=1);

namespace App\Domain\Workload\Calculators;

use App\Domain\Workload\ValueObjects\PlannedBlock;
use App\Enums\Workout\BlockType;

class DurationEstimator
{
    /**
     * @param  array<PlannedBlock>  $blocks
     */
    public function estimate(array $blocks): ?int
    {
        $total = 0;
        $hasEstimate = false;

        foreach ($blocks as $block) {
            $blockDuration = $this->estimateBlock($block);

            if ($blockDuration !== null) {
                $total += $blockDuration;
                $hasEstimate = true;
            }
        }

        return $hasEstimate ? $total : null;
    }

    private function estimateBlock(PlannedBlock $block): ?int
    {
        return match ($block->blockType) {
            BlockType::DistanceDuration => $this->estimateDistanceDuration($block),
            BlockType::Interval => $this->estimateInterval($block),
            BlockType::Amrap => $block->timeCap,
            BlockType::Emom => $this->estimateEmom($block),
            BlockType::ForTime => $block->timeCap,
            BlockType::Circuit => $this->estimateCircuit($block),
            BlockType::Superset => $this->estimateSuperset($block),
            BlockType::StraightSets => $this->estimateStraightSets($block),
            BlockType::Rest => $this->sumExerciseDurations($block),
        };
    }

    private function estimateDistanceDuration(PlannedBlock $block): ?int
    {
        return $this->sumExerciseDurations($block);
    }

    private function estimateInterval(PlannedBlock $block): ?int
    {
        if ($block->rounds === null || $block->workInterval === null || $block->restInterval === null) {
            return null;
        }

        return $block->rounds * ($block->workInterval + $block->restInterval) - $block->restInterval;
    }

    private function estimateEmom(PlannedBlock $block): ?int
    {
        if ($block->rounds === null || $block->workInterval === null) {
            return null;
        }

        return $block->rounds * $block->workInterval;
    }

    private function estimateCircuit(PlannedBlock $block): ?int
    {
        $sumDurations = $this->sumExerciseDurations($block);

        if ($sumDurations === null || $block->rounds === null) {
            return null;
        }

        $exerciseCount = count($block->exerciseDurations);
        $restBetweenExercises = $block->restBetweenExercises ?? 0;
        $restBetweenRounds = $block->restBetweenRounds ?? 0;

        $roundDuration = $sumDurations + $restBetweenExercises * max($exerciseCount - 1, 0);

        return $block->rounds * $roundDuration + $restBetweenRounds * max($block->rounds - 1, 0);
    }

    private function estimateSuperset(PlannedBlock $block): ?int
    {
        $sumDurations = $this->sumExerciseDurations($block);

        if ($sumDurations === null || $block->rounds === null) {
            return null;
        }

        $restBetweenRounds = $block->restBetweenRounds ?? 0;

        return $block->rounds * $sumDurations + $restBetweenRounds * max($block->rounds - 1, 0);
    }

    private function estimateStraightSets(PlannedBlock $block): ?int
    {
        return $this->sumExerciseDurations($block);
    }

    private function sumExerciseDurations(PlannedBlock $block): ?int
    {
        $sum = 0;
        $hasValue = false;

        foreach ($block->exerciseDurations as $duration) {
            if ($duration !== null) {
                $sum += $duration;
                $hasValue = true;
            }
        }

        return $hasValue ? $sum : null;
    }
}
