<?php

namespace App\Actions;

use App\DataTransferObjects\Workout\CardioExerciseData;
use App\DataTransferObjects\Workout\DurationExerciseData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\SectionData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Workout\Activity;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateStructuredWorkout
{
    /**
     * @param  Collection<int, SectionData>  $sections
     */
    public function execute(
        User $user,
        string $name,
        Activity $activity,
        CarbonImmutable $scheduledAt,
        ?string $notes,
        Collection $sections,
    ): Workout {
        return DB::transaction(function () use ($user, $name, $activity, $scheduledAt, $notes, $sections): Workout {
            $workout = Workout::create([
                'user_id' => $user->getKey(),
                'name' => $name,
                'activity' => $activity,
                'scheduled_at' => $scheduledAt,
                'notes' => $notes,
            ]);

            $this->buildSections($workout, $sections);

            return $workout->load('sections.blocks.exercises.exerciseable');
        });
    }

    /**
     * @param  Collection<int, SectionData>  $sections
     */
    public function buildSections(Workout $workout, Collection $sections): void
    {
        foreach ($sections as $sectionData) {
            $section = $workout->sections()->create([
                'name' => $sectionData->name,
                'order' => $sectionData->order,
                'notes' => $sectionData->notes,
            ]);

            foreach ($sectionData->blocks as $blockData) {
                $block = $section->blocks()->create([
                    'block_type' => $blockData->blockType,
                    'order' => $blockData->order,
                    'rounds' => $blockData->rounds,
                    'rest_between_exercises' => $blockData->restBetweenExercises,
                    'rest_between_rounds' => $blockData->restBetweenRounds,
                    'time_cap' => $blockData->timeCap,
                    'work_interval' => $blockData->workInterval,
                    'rest_interval' => $blockData->restInterval,
                    'notes' => $blockData->notes,
                ]);

                foreach ($blockData->exercises as $exerciseData) {
                    $exerciseable = $this->createExerciseable($exerciseData);

                    $block->exercises()->create([
                        'name' => $exerciseData->name,
                        'order' => $exerciseData->order,
                        'exerciseable_type' => $exerciseable->getMorphClass(),
                        'exerciseable_id' => $exerciseable->getKey(),
                        'notes' => $exerciseData->notes,
                    ]);
                }
            }
        }
    }

    protected function createExerciseable(ExerciseData $data): StrengthExercise|CardioExercise|DurationExercise
    {
        return match (true) {
            $data->exerciseable instanceof StrengthExerciseData => StrengthExercise::create([
                'target_sets' => $data->exerciseable->targetSets,
                'target_reps_min' => $data->exerciseable->targetRepsMin,
                'target_reps_max' => $data->exerciseable->targetRepsMax,
                'target_weight' => $data->exerciseable->targetWeight,
                'target_rpe' => $data->exerciseable->targetRpe,
                'target_tempo' => $data->exerciseable->targetTempo,
                'rest_after' => $data->exerciseable->restAfter,
            ]),
            $data->exerciseable instanceof CardioExerciseData => CardioExercise::create([
                'target_duration' => $data->exerciseable->targetDuration,
                'target_distance' => $data->exerciseable->targetDistance,
                'target_pace_min' => $data->exerciseable->targetPaceMin,
                'target_pace_max' => $data->exerciseable->targetPaceMax,
                'target_heart_rate_zone' => $data->exerciseable->targetHeartRateZone,
                'target_heart_rate_min' => $data->exerciseable->targetHeartRateMin,
                'target_heart_rate_max' => $data->exerciseable->targetHeartRateMax,
                'target_power' => $data->exerciseable->targetPower,
            ]),
            $data->exerciseable instanceof DurationExerciseData => DurationExercise::create([
                'target_duration' => $data->exerciseable->targetDuration,
                'target_rpe' => $data->exerciseable->targetRpe,
            ]),
        };
    }
}
