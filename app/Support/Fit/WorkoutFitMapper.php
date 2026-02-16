<?php

declare(strict_types=1);

namespace App\Support\Fit;

use App\Enums\Workout\BlockType;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\Workout;
use App\Support\Workout\WorkoutDisplayFormatter as Format;

class WorkoutFitMapper
{
    /** @var list<FitMessage> */
    private array $steps = [];

    private int $stepIndex = 0;

    /** @var array<string, array{category: int, name: int, label: string}> */
    private array $exerciseTitles = [];

    /**
     * @return list<FitMessage>
     */
    public function map(Workout $workout): array
    {
        $this->steps = [];
        $this->stepIndex = 0;
        $this->exerciseTitles = [];

        foreach ($workout->sections as $section) {
            $intensity = $this->sectionIntensity($section);

            foreach ($section->blocks as $block) {
                $this->mapBlock($block, $intensity);
            }
        }

        $sportMapping = SportMapper::fromActivity($workout->activity);

        return [
            FitMessageFactory::fileId(),
            FitMessageFactory::workout(
                name: $workout->name,
                sport: $sportMapping->sport,
                subSport: $sportMapping->subSport,
                numSteps: count($this->steps),
            ),
            ...$this->steps,
            ...$this->buildExerciseTitles(),
        ];
    }

    /**
     * @return list<FitMessage>
     */
    private function buildExerciseTitles(): array
    {
        $messages = [];
        $index = 0;

        foreach ($this->exerciseTitles as $title) {
            $messages[] = FitMessageFactory::exerciseTitle(
                messageIndex: $index++,
                exerciseCategory: $title['category'],
                exerciseName: $title['name'],
                stepName: $title['label'],
            );
        }

        return $messages;
    }

    private function registerExerciseTitle(string $label, ?int $exerciseCategory, ?int $exerciseName): void
    {
        if ($exerciseCategory === null || $exerciseName === null) {
            return;
        }

        $key = "{$exerciseCategory}:{$exerciseName}";

        if (! isset($this->exerciseTitles[$key])) {
            $this->exerciseTitles[$key] = [
                'category' => $exerciseCategory,
                'name' => $exerciseName,
                'label' => $label,
            ];
        }
    }

    private function sectionIntensity(Section $section): int
    {
        $name = strtolower($section->name);

        if (str_contains($name, 'warm')) {
            return 2; // WARMUP
        }

        if (str_contains($name, 'cool')) {
            return 3; // COOLDOWN
        }

        return 0; // ACTIVE
    }

    private function mapBlock(Block $block, int $sectionIntensity): void
    {
        match ($block->block_type) {
            BlockType::Rest => $this->mapRest($block),
            BlockType::StraightSets => $this->mapStraightSets($block, $sectionIntensity),
            BlockType::Circuit => $this->mapCircuit($block, $sectionIntensity),
            BlockType::Superset => $this->mapSuperset($block, $sectionIntensity),
            BlockType::Interval => $this->mapInterval($block, $sectionIntensity),
            BlockType::Amrap => $this->mapAmrap($block, $sectionIntensity),
            BlockType::ForTime => $this->mapForTime($block, $sectionIntensity),
            BlockType::Emom => $this->mapEmom($block, $sectionIntensity),
            BlockType::DistanceDuration => $this->mapDistanceDuration($block, $sectionIntensity),
        };
    }

    private function mapRest(Block $block): void
    {
        $exercise = $block->exercises->first();
        $duration = 60; // default

        if ($exercise?->exerciseable instanceof DurationExercise && $exercise->exerciseable->target_duration) {
            $duration = $exercise->exerciseable->target_duration;
        }

        $this->addTimeStep($exercise?->name ?? 'Rest', $duration, 1); // intensity REST
    }

    private function mapStraightSets(Block $block, int $sectionIntensity): void
    {
        foreach ($block->exercises as $exercise) {
            $startIndex = $this->stepIndex;

            $this->addExerciseStep($exercise, $sectionIntensity);

            $sets = null;
            $restAfter = null;

            if ($exercise->exerciseable instanceof StrengthExercise) {
                $sets = $exercise->exerciseable->target_sets;
                $restAfter = $exercise->exerciseable->rest_after;
            }

            if ($restAfter && $restAfter > 0) {
                $this->addRestStep('Rest', $restAfter);
            }

            if ($sets && $sets > 1) {
                $this->addRepeatStep($startIndex, $sets);
            }
        }
    }

    private function mapCircuit(Block $block, int $sectionIntensity): void
    {
        $startIndex = $this->stepIndex;
        $exercises = $block->exercises;

        foreach ($exercises as $index => $exercise) {
            $this->addExerciseStep($exercise, $sectionIntensity);

            $isLast = $index === $exercises->count() - 1;

            if (! $isLast && $block->rest_between_exercises && $block->rest_between_exercises > 0) {
                $this->addRestStep('Rest', $block->rest_between_exercises);
            }
        }

        if ($block->rest_between_rounds && $block->rest_between_rounds > 0) {
            $this->addRestStep('Rest', $block->rest_between_rounds);
        }

        if ($block->rounds && $block->rounds > 1) {
            $this->addRepeatStep($startIndex, $block->rounds);
        }
    }

    private function mapSuperset(Block $block, int $sectionIntensity): void
    {
        $startIndex = $this->stepIndex;

        foreach ($block->exercises as $exercise) {
            $this->addExerciseStep($exercise, $sectionIntensity);
        }

        if ($block->rest_between_rounds && $block->rest_between_rounds > 0) {
            $this->addRestStep('Rest', $block->rest_between_rounds);
        }

        if ($block->rounds && $block->rounds > 1) {
            $this->addRepeatStep($startIndex, $block->rounds);
        }
    }

    private function mapInterval(Block $block, int $sectionIntensity): void
    {
        $startIndex = $this->stepIndex;

        if ($block->work_interval && $block->work_interval > 0) {
            $name = $block->exercises->first()?->name ?? 'Work';
            $this->addTimeStep($name, $block->work_interval, $sectionIntensity);
        } else {
            foreach ($block->exercises as $exercise) {
                $this->addExerciseStep($exercise, $sectionIntensity);
            }
        }

        if ($block->rest_interval && $block->rest_interval > 0) {
            $this->addRestStep('Rest', $block->rest_interval);
        }

        if ($block->rounds && $block->rounds > 1) {
            $this->addRepeatStep($startIndex, $block->rounds);
        }
    }

    private function mapAmrap(Block $block, int $sectionIntensity): void
    {
        $startIndex = $this->stepIndex;

        foreach ($block->exercises as $exercise) {
            $this->addExerciseStep($exercise, $sectionIntensity);
        }

        if ($block->time_cap && $block->time_cap > 0) {
            $this->addRepeatUntilTimeStep($startIndex, $block->time_cap);
        }
    }

    private function mapForTime(Block $block, int $sectionIntensity): void
    {
        $startIndex = $this->stepIndex;

        foreach ($block->exercises as $exercise) {
            $this->addExerciseStep($exercise, $sectionIntensity);
        }

        if ($block->rounds && $block->rounds > 1) {
            $this->addRepeatStep($startIndex, $block->rounds);
        }
    }

    private function mapEmom(Block $block, int $sectionIntensity): void
    {
        $startIndex = $this->stepIndex;
        $name = $block->exercises->first()?->name ?? 'Work';

        if ($block->work_interval && $block->work_interval > 0) {
            $this->addTimeStep($name, $block->work_interval, $sectionIntensity);
        }

        if ($block->rounds && $block->rounds > 1) {
            $this->addRepeatStep($startIndex, $block->rounds);
        }
    }

    private function mapDistanceDuration(Block $block, int $sectionIntensity): void
    {
        foreach ($block->exercises as $exercise) {
            $exerciseable = $exercise->exerciseable;
            $garminCategory = $exercise->exercise?->garmin_exercise_category?->value;
            $garminName = $exercise->exercise?->garmin_exercise_name;

            if ($exerciseable instanceof CardioExercise) {
                $target = $this->cardioTarget($exerciseable);

                if ($exerciseable->target_distance && (float) $exerciseable->target_distance > 0) {
                    $this->addDistanceStep(
                        $exercise->name,
                        (int) round((float) $exerciseable->target_distance * 100), // meters to centimeters
                        $sectionIntensity,
                        $target,
                        $garminCategory,
                        $garminName,
                    );
                } elseif ($exerciseable->target_duration && $exerciseable->target_duration > 0) {
                    $this->addStep(
                        $exercise->name,
                        0, // TIME
                        $exerciseable->target_duration * 1000,
                        $target['targetType'],
                        $target['targetValue'],
                        $target['customLow'],
                        $target['customHigh'],
                        $sectionIntensity,
                        exerciseCategory: $garminCategory,
                        exerciseName: $garminName,
                    );
                } else {
                    $this->addStep(
                        $exercise->name,
                        5, // OPEN
                        null,
                        $target['targetType'],
                        $target['targetValue'],
                        $target['customLow'],
                        $target['customHigh'],
                        $sectionIntensity,
                        exerciseCategory: $garminCategory,
                        exerciseName: $garminName,
                    );
                }
            } elseif ($exerciseable instanceof DurationExercise && $exerciseable->target_duration) {
                $this->addTimeStep($exercise->name, $exerciseable->target_duration, $sectionIntensity, $garminCategory, $garminName);
            } else {
                $this->addOpenStep($exercise->name, $sectionIntensity, exerciseCategory: $garminCategory, exerciseName: $garminName);
            }
        }
    }

    private function addExerciseStep(BlockExercise $exercise, int $intensity): void
    {
        $exerciseable = $exercise->exerciseable;
        $garminCategory = $exercise->exercise?->garmin_exercise_category?->value;
        $garminName = $exercise->exercise?->garmin_exercise_name;

        if ($exerciseable instanceof StrengthExercise) {
            $this->addOpenStep($exercise->name, $intensity, $this->strengthNotes($exercise->name, $exerciseable), $garminCategory, $garminName);
        } elseif ($exerciseable instanceof CardioExercise) {
            $target = $this->cardioTarget($exerciseable);

            if ($exerciseable->target_distance && (float) $exerciseable->target_distance > 0) {
                $this->addDistanceStep(
                    $exercise->name,
                    (int) round((float) $exerciseable->target_distance * 100),
                    $intensity,
                    $target,
                    $garminCategory,
                    $garminName,
                );
            } elseif ($exerciseable->target_duration && $exerciseable->target_duration > 0) {
                $this->addStep(
                    $exercise->name,
                    0, // TIME
                    $exerciseable->target_duration * 1000,
                    $target['targetType'],
                    $target['targetValue'],
                    $target['customLow'],
                    $target['customHigh'],
                    $intensity,
                    exerciseCategory: $garminCategory,
                    exerciseName: $garminName,
                );
            } else {
                $this->addStep(
                    $exercise->name,
                    5, // OPEN
                    null,
                    $target['targetType'],
                    $target['targetValue'],
                    $target['customLow'],
                    $target['customHigh'],
                    $intensity,
                    exerciseCategory: $garminCategory,
                    exerciseName: $garminName,
                );
            }
        } elseif ($exerciseable instanceof DurationExercise && $exerciseable->target_duration) {
            $this->addTimeStep($exercise->name, $exerciseable->target_duration, $intensity, $garminCategory, $garminName);
        } else {
            $this->addOpenStep($exercise->name, $intensity, exerciseCategory: $garminCategory, exerciseName: $garminName);
        }
    }

    private function addOpenStep(string $name, int $intensity, ?string $notes = null, ?int $exerciseCategory = null, ?int $exerciseName = null): void
    {
        $this->addStep($name, 5, null, 2, null, null, null, $intensity, $notes, $exerciseCategory, $exerciseName);
    }

    private function addTimeStep(string $name, int $durationSeconds, int $intensity, ?int $exerciseCategory = null, ?int $exerciseName = null): void
    {
        $this->addStep($name, 0, $durationSeconds * 1000, 2, null, null, null, $intensity, exerciseCategory: $exerciseCategory, exerciseName: $exerciseName);
    }

    private function addRestStep(string $name, int $durationSeconds): void
    {
        $this->addStep($name, 0, $durationSeconds * 1000, 2, null, null, null, 1);
    }

    /**
     * @param  array{targetType: int, targetValue: int|null, customLow: int|null, customHigh: int|null}  $target
     */
    private function addDistanceStep(string $name, int $distanceCm, int $intensity, array $target, ?int $exerciseCategory = null, ?int $exerciseName = null): void
    {
        $this->addStep(
            $name,
            1, // DISTANCE
            $distanceCm,
            $target['targetType'],
            $target['targetValue'],
            $target['customLow'],
            $target['customHigh'],
            $intensity,
            exerciseCategory: $exerciseCategory,
            exerciseName: $exerciseName,
        );
    }

    private function addRepeatStep(int $backToIndex, int $count): void
    {
        $this->addStep(null, 6, $backToIndex, 2, $count, null, null, 0);
    }

    private function addRepeatUntilTimeStep(int $backToIndex, int $durationSeconds): void
    {
        $this->addStep(null, 7, $backToIndex, 2, $durationSeconds * 1000, null, null, 0);
    }

    private function addStep(
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

    private function strengthNotes(string $exerciseName, StrengthExercise $exercise): string
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

    /**
     * @return array{targetType: int, targetValue: int|null, customLow: int|null, customHigh: int|null}
     */
    private function cardioTarget(CardioExercise $exercise): array
    {
        if ($exercise->target_heart_rate_zone !== null) {
            return [
                'targetType' => 1, // HEART_RATE
                'targetValue' => $exercise->target_heart_rate_zone,
                'customLow' => null,
                'customHigh' => null,
            ];
        }

        if ($exercise->target_heart_rate_min !== null || $exercise->target_heart_rate_max !== null) {
            return [
                'targetType' => 1, // HEART_RATE
                'targetValue' => 0,
                'customLow' => $exercise->target_heart_rate_min !== null ? $exercise->target_heart_rate_min + 100 : null,
                'customHigh' => $exercise->target_heart_rate_max !== null ? $exercise->target_heart_rate_max + 100 : null,
            ];
        }

        if ($exercise->target_pace_min !== null || $exercise->target_pace_max !== null) {
            return [
                'targetType' => 0, // SPEED
                'targetValue' => 0,
                'customLow' => $exercise->target_pace_min !== null ? (int) round(1_000_000 / $exercise->target_pace_min) : null,
                'customHigh' => $exercise->target_pace_max !== null ? (int) round(1_000_000 / $exercise->target_pace_max) : null,
            ];
        }

        if ($exercise->target_power !== null) {
            return [
                'targetType' => 4, // POWER
                'targetValue' => 0,
                'customLow' => $exercise->target_power,
                'customHigh' => $exercise->target_power,
            ];
        }

        return [
            'targetType' => 2, // OPEN
            'targetValue' => null,
            'customLow' => null,
            'customHigh' => null,
        ];
    }
}
