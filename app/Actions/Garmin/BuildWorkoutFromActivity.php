<?php

declare(strict_types=1);

namespace App\Actions\Garmin;

use App\DataTransferObjects\Fit\BuildWorkoutResult;
use App\DataTransferObjects\Fit\DetectedBlock;
use App\DataTransferObjects\Fit\ExerciseGroup;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Workout\BlockData;
use App\DataTransferObjects\Workout\CardioExerciseData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\SectionData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Fit\DetectedBlockType;
use App\Enums\Fit\GarminExerciseCategory;
use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseType;
use App\Models\Exercise;
use Illuminate\Support\Collection;

class BuildWorkoutFromActivity
{
    public function __construct(private MatchGarminExercise $matcher) {}

    /**
     * @param  array<int, int>  $exerciseMappings  Map of exercise group index → Exercise model ID
     */
    public function execute(ParsedActivity $activity, array $exerciseMappings = [], bool $detectSupersets = false): BuildWorkoutResult
    {
        $matched = [];
        $unmatched = [];
        $warnings = [];

        $blocks = collect();
        $blockOrder = 0;
        $hasSets = count($activity->sets) > 0;
        $hasCardioLaps = collect($activity->laps)->contains(fn ($lap) => ($lap->totalDistance ?? 0) > 0);

        if ($hasSets) {
            $strengthBlocks = $this->buildStrengthBlocks($activity, $matched, $unmatched, $warnings, $blockOrder, $exerciseMappings, $detectSupersets);
            $blocks = $blocks->merge($strengthBlocks);
            $blockOrder += $strengthBlocks->count();
        }

        if ($hasCardioLaps) {
            $blocks->push($this->buildCardioBlock($activity, $blockOrder));
        }

        if ($blocks->isEmpty()) {
            $blocks->push($this->buildCardioBlock($activity, 0));
        }

        $sections = collect([
            new SectionData(
                name: 'Workout',
                order: 0,
                blocks: $blocks,
            ),
        ]);

        return new BuildWorkoutResult(
            sections: $sections,
            matched: $matched,
            unmatched: $unmatched,
            warnings: $warnings,
        );
    }

    /**
     * @param  list<string>  $matched
     * @param  list<string>  $unmatched
     * @param  list<string>  $warnings
     * @param  array<int, int>  $exerciseMappings
     * @return Collection<int, BlockData>
     */
    private function buildStrengthBlocks(
        ParsedActivity $activity,
        array &$matched,
        array &$unmatched,
        array &$warnings,
        int $startOrder = 0,
        array $exerciseMappings = [],
        bool $detectSupersets = true,
    ): Collection {
        $titleMap = ParsedActivityHelper::buildTitleMap($activity);
        $activeSets = collect($activity->sets)->filter->isActive();
        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);

        if ($detectSupersets) {
            $detectedBlocks = ParsedActivityHelper::detectBlocks($groups);
        } else {
            $detectedBlocks = array_map(
                fn (ExerciseGroup $group) => new DetectedBlock(
                    DetectedBlockType::Straight,
                    [$group->key => $group],
                ),
                $groups,
            );
        }

        $blocks = collect();
        $blockOrder = $startOrder;
        $globalExerciseIndex = 0;

        foreach ($detectedBlocks as $detected) {
            $exercises = collect();
            $exerciseOrder = 0;

            foreach ($detected->exercises as $key => $exerciseInfo) {
                // Check for user-provided exercise mapping first
                $mappedExercise = isset($exerciseMappings[$globalExerciseIndex])
                    ? Exercise::find($exerciseMappings[$globalExerciseIndex])
                    : null;

                if ($mappedExercise) {
                    $exercise = $mappedExercise;
                    $displayName = $mappedExercise->name;
                    $matched[] = $mappedExercise->name;
                } elseif (str_starts_with($key, 'weight:')) {
                    $displayName = 'Exercise '.($globalExerciseIndex + 1);
                    $exercise = null;
                } else {
                    $category = GarminExerciseCategory::tryFrom($exerciseInfo->category);
                    $categoryLabel = $category?->label();
                    $displayName = $titleMap[$key] ?? $categoryLabel ?? "Exercise {$exerciseInfo->category}/{$exerciseInfo->name}";

                    $exercise = ($category === GarminExerciseCategory::Unknown)
                        ? null
                        : $this->matchExercise($exerciseInfo->category, $exerciseInfo->name, $displayName, $matched, $unmatched, $warnings);
                }

                $reps = collect($exerciseInfo->sets)->pluck('repetitions')->filter()->values();
                $weights = collect($exerciseInfo->sets)->pluck('weight')->filter()->values();

                $exercises->push(new ExerciseData(
                    name: $exercise?->name ?? $displayName,
                    order: $exerciseOrder,
                    type: ExerciseType::Strength,
                    exerciseable: new StrengthExerciseData(
                        targetSets: count($exerciseInfo->sets),
                        targetRepsMin: $reps->isNotEmpty() ? (int) $reps->min() : null,
                        targetRepsMax: $reps->isNotEmpty() ? (int) $reps->max() : null,
                        targetWeight: $weights->isNotEmpty() ? (float) $weights->max() : null,
                    ),
                    exerciseId: $exercise?->id,
                ));

                $exerciseOrder++;
                $globalExerciseIndex++;
            }

            $blockType = $detected->type === DetectedBlockType::Superset ? BlockType::Superset : BlockType::StraightSets;

            $blocks->push(new BlockData(
                blockType: $blockType,
                order: $blockOrder,
                exercises: $exercises,
            ));

            $blockOrder++;
        }

        return $blocks;
    }

    private function buildCardioBlock(ParsedActivity $activity, int $order = 0): BlockData
    {
        $mappedActivity = SportMapper::toActivity($activity->session->sport, $activity->session->subSport);
        $activityLabel = $mappedActivity->label();

        $exercise = Exercise::query()
            ->where('name', $activityLabel)
            ->first();

        return new BlockData(
            blockType: BlockType::DistanceDuration,
            order: $order,
            exercises: collect([
                new ExerciseData(
                    name: $exercise?->name ?? $activity->session->workoutName ?? $activityLabel,
                    order: 0,
                    type: ExerciseType::Cardio,
                    exerciseable: new CardioExerciseData(
                        targetDuration: $activity->session->totalElapsedTime,
                        targetDistance: $activity->session->totalDistance !== null ? (float) $activity->session->totalDistance : null,
                    ),
                    exerciseId: $exercise?->id,
                ),
            ]),
        );
    }

    /**
     * @param  list<string>  $matched
     * @param  list<string>  $unmatched
     * @param  list<string>  $warnings
     */
    private function matchExercise(
        int $category,
        int $name,
        string $displayName,
        array &$matched,
        array &$unmatched,
        array &$warnings,
    ): ?Exercise {
        $exercise = $this->matcher->match($category, $name);

        if ($exercise !== null) {
            $matched[] = $exercise->name;
        } else {
            $unmatched[] = $displayName;
            $warnings[] = "Could not match exercise: {$displayName}";
        }

        return $exercise;
    }
}
