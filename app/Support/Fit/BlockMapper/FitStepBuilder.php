<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;
use App\Support\Fit\FitExerciseTitleEntry;
use App\Support\Fit\FitMessage;
use App\Support\Fit\FitMessageFactory;
use App\Support\Fit\FitStepTarget;
use App\Support\Workout\WorkoutDisplayFormatter as Format;

class FitStepBuilder
{
    /** @var list<FitMessage> */
    private array $steps = [];

    private int $stepIndex = 0;

    /** @var array<string, FitExerciseTitleEntry> */
    private array $exerciseTitles = [];

    /**
     * @return list<FitMessage>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getStepCount(): int
    {
        return count($this->steps);
    }

    public function getStepIndex(): int
    {
        return $this->stepIndex;
    }

    /**
     * @return list<FitMessage>
     */
    public function buildExerciseTitleMessages(): array
    {
        $messages = [];
        $index = 0;

        foreach ($this->exerciseTitles as $title) {
            $messages[] = FitMessageFactory::exerciseTitle(
                messageIndex: $index++,
                exerciseCategory: $title->category,
                exerciseName: $title->name,
                stepName: $title->label,
            );
        }

        return $messages;
    }

    public function addExerciseStep(BlockExercise $exercise, int $intensity): void
    {
        $exerciseable = $exercise->exerciseable;
        $garminCategory = $exercise->exercise?->garmin_exercise_category?->value;
        $garminName = $exercise->exercise?->garmin_exercise_name;

        if ($exerciseable instanceof StrengthExercise) {
            $this->addOpenStep($exercise->name, $intensity, $this->strengthNotes($exercise->name, $exerciseable), $garminCategory, $garminName);
        } elseif ($exerciseable instanceof CardioExercise) {
            $this->addCardioExerciseStep($exercise->name, $exerciseable, $intensity, $garminCategory, $garminName);
        } elseif ($exerciseable instanceof DurationExercise && $exerciseable->target_duration) {
            $this->addTimeStep($exercise->name, $exerciseable->target_duration, $intensity, $garminCategory, $garminName);
        } else {
            $this->addOpenStep($exercise->name, $intensity, exerciseCategory: $garminCategory, exerciseName: $garminName);
        }
    }

    public function addCardioExerciseStep(string $name, CardioExercise $exerciseable, int $intensity, ?int $garminCategory = null, ?int $garminName = null): void
    {
        $target = $this->cardioTarget($exerciseable);

        if ($exerciseable->target_distance && (float) $exerciseable->target_distance > 0) {
            $this->addDistanceStep(
                $name,
                (int) round((float) $exerciseable->target_distance * 100),
                $intensity,
                $target,
                $garminCategory,
                $garminName,
            );
        } elseif ($exerciseable->target_duration && $exerciseable->target_duration > 0) {
            $this->addStep(
                $name,
                0, // TIME
                $exerciseable->target_duration * 1000,
                $target->targetType,
                $target->targetValue,
                $target->customLow,
                $target->customHigh,
                $intensity,
                exerciseCategory: $garminCategory,
                exerciseName: $garminName,
            );
        } else {
            $this->addStep(
                $name,
                5, // OPEN
                null,
                $target->targetType,
                $target->targetValue,
                $target->customLow,
                $target->customHigh,
                $intensity,
                exerciseCategory: $garminCategory,
                exerciseName: $garminName,
            );
        }
    }

    public function addOpenStep(string $name, int $intensity, ?string $notes = null, ?int $exerciseCategory = null, ?int $exerciseName = null): void
    {
        $this->addStep($name, 5, null, 2, null, null, null, $intensity, $notes, $exerciseCategory, $exerciseName);
    }

    public function addTimeStep(string $name, int $durationSeconds, int $intensity, ?int $exerciseCategory = null, ?int $exerciseName = null, ?string $notes = null): void
    {
        $this->addStep($name, 0, $durationSeconds * 1000, 2, null, null, null, $intensity, $notes, $exerciseCategory, $exerciseName);
    }

    public function addRestStep(string $name, int $durationSeconds, ?string $notes = null): void
    {
        $this->addStep($name, 0, $durationSeconds * 1000, 2, null, null, null, 1, $notes);
    }

    public function addDistanceStep(string $name, int $distanceCm, int $intensity, FitStepTarget $target, ?int $exerciseCategory = null, ?int $exerciseName = null): void
    {
        $this->addStep(
            $name,
            1, // DISTANCE
            $distanceCm,
            $target->targetType,
            $target->targetValue,
            $target->customLow,
            $target->customHigh,
            $intensity,
            exerciseCategory: $exerciseCategory,
            exerciseName: $exerciseName,
        );
    }

    public function addRepeatStep(int $backToIndex, int $count): void
    {
        $this->addStep(null, 6, $backToIndex, 2, $count, null, null, 0);
    }

    public function addRepeatUntilTimeStep(int $backToIndex, int $durationSeconds): void
    {
        $this->addStep(null, 7, $backToIndex, 2, $durationSeconds * 1000, null, null, 0);
    }

    public function addStep(
        ?string $name,
        int $durationType,
        ?int $durationValue,
        int $targetType,
        ?int $targetValue,
        ?int $customTargetLow,
        ?int $customTargetHigh,
        int $intensity,
        ?string $notes = null,
        ?int $exerciseCategory = null,
        ?int $exerciseName = null,
    ): void {
        if ($name !== null) {
            $this->registerExerciseTitle($name, $exerciseCategory, $exerciseName);
        }

        $this->steps[] = FitMessageFactory::workoutStep(
            messageIndex: $this->stepIndex,
            stepName: $name,
            durationType: $durationType,
            durationValue: $durationValue,
            targetType: $targetType,
            targetValue: $targetValue,
            customTargetLow: $customTargetLow,
            customTargetHigh: $customTargetHigh,
            intensity: $intensity,
            notes: $notes,
            exerciseCategory: $exerciseCategory,
            exerciseName: $exerciseName,
        );
        $this->stepIndex++;
    }

    public function strengthNotes(string $exerciseName, StrengthExercise $exercise): string
    {
        $parts = [];

        $setsReps = Format::setsReps($exercise->target_sets, $exercise->target_reps_min, $exercise->target_reps_max);
        if ($setsReps) {
            $parts[] = $setsReps;
        }

        $weight = Format::weight($exercise->target_weight);
        if ($weight) {
            $parts[] = "@ {$weight}";
        }

        if ($exercise->target_rpe) {
            $formatted = (floor((float) $exercise->target_rpe) == (float) $exercise->target_rpe)
                ? (int) $exercise->target_rpe
                : $exercise->target_rpe;
            $parts[] = "RPE {$formatted}";
        }

        if ($exercise->target_tempo) {
            $parts[] = "Tempo {$exercise->target_tempo}";
        }

        $rest = Format::rest($exercise->rest_after);
        if ($rest) {
            $parts[] = "Rest {$rest}";
        }

        $details = empty($parts) ? '' : "\n".implode(', ', $parts);

        return "{$exerciseName}{$details}";
    }

    public function cardioTarget(CardioExercise $exercise): FitStepTarget
    {
        if ($exercise->target_heart_rate_zone !== null) {
            return new FitStepTarget(1, $exercise->target_heart_rate_zone, null, null);
        }

        if ($exercise->target_heart_rate_min !== null || $exercise->target_heart_rate_max !== null) {
            return new FitStepTarget(
                1, // HEART_RATE
                0,
                $exercise->target_heart_rate_min !== null ? $exercise->target_heart_rate_min + 100 : null,
                $exercise->target_heart_rate_max !== null ? $exercise->target_heart_rate_max + 100 : null,
            );
        }

        if ($exercise->target_pace_min !== null || $exercise->target_pace_max !== null) {
            return new FitStepTarget(
                0, // SPEED
                0,
                $exercise->target_pace_min !== null ? (int) round(1_000_000 / $exercise->target_pace_min) : null,
                $exercise->target_pace_max !== null ? (int) round(1_000_000 / $exercise->target_pace_max) : null,
            );
        }

        if ($exercise->target_power !== null) {
            return new FitStepTarget(4, 0, $exercise->target_power, $exercise->target_power);
        }

        return new FitStepTarget(2, null, null, null);
    }

    /**
     * @param  list<BlockExercise>  $exercises
     */
    public function intervalWorkNotes(array $exercises, ?string $blockNotes): ?string
    {
        $lines = [];

        foreach ($exercises as $exercise) {
            $lines[] = $exercise->name;

            $targets = [];
            $exerciseable = $exercise->exerciseable;

            if ($exerciseable instanceof CardioExercise) {
                $hrZone = Format::hrZone($exerciseable->target_heart_rate_zone);
                if ($hrZone) {
                    $targets[] = $hrZone;
                }

                $hrRange = Format::hrRange($exerciseable->target_heart_rate_min, $exerciseable->target_heart_rate_max);
                if ($hrRange) {
                    $targets[] = $hrRange;
                }

                $paceRange = Format::paceRange($exerciseable->target_pace_min, $exerciseable->target_pace_max);
                if ($paceRange) {
                    $targets[] = $paceRange;
                }

                $power = Format::power($exerciseable->target_power);
                if ($power) {
                    $targets[] = $power;
                }
            } elseif ($exerciseable instanceof DurationExercise) {
                $rpe = Format::rpe($exerciseable->target_rpe);
                if ($rpe) {
                    $targets[] = $rpe;
                }
            }

            if (! empty($targets)) {
                $lines[] = implode(', ', $targets);
            }

            if ($exercise->notes) {
                $lines[] = $exercise->notes;
            }
        }

        if ($blockNotes) {
            $lines[] = $blockNotes;
        }

        return empty($lines) ? null : implode("\n", $lines);
    }

    private function registerExerciseTitle(string $label, ?int $exerciseCategory, ?int $exerciseName): void
    {
        if ($exerciseCategory === null || $exerciseName === null) {
            return;
        }

        $key = "{$exerciseCategory}:{$exerciseName}";

        if (! isset($this->exerciseTitles[$key])) {
            $this->exerciseTitles[$key] = new FitExerciseTitleEntry($exerciseCategory, $exerciseName, $label);
        }
    }
}
