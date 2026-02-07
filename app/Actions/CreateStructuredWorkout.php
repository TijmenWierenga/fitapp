<?php

namespace App\Actions;

use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateStructuredWorkout
{
    /**
     * @param  array<int, array{name: string, order: int, notes?: string|null, blocks: array<int, mixed>}>  $sections
     */
    public function execute(
        User $user,
        string $name,
        Activity $activity,
        CarbonImmutable $scheduledAt,
        ?string $notes,
        array $sections,
    ): Workout {
        return DB::transaction(function () use ($user, $name, $activity, $scheduledAt, $notes, $sections): Workout {
            $workout = Workout::create([
                'user_id' => $user->getKey(),
                'name' => $name,
                'activity' => $activity,
                'scheduled_at' => $scheduledAt,
                'notes' => $notes,
            ]);

            foreach ($sections as $sectionData) {
                $section = $workout->sections()->create([
                    'name' => $sectionData['name'],
                    'order' => $sectionData['order'],
                    'notes' => $sectionData['notes'] ?? null,
                ]);

                foreach ($sectionData['blocks'] ?? [] as $blockData) {
                    $block = $section->blocks()->create([
                        'block_type' => BlockType::from($blockData['block_type']),
                        'order' => $blockData['order'],
                        'rounds' => $blockData['rounds'] ?? null,
                        'rest_between_exercises' => $blockData['rest_between_exercises'] ?? null,
                        'rest_between_rounds' => $blockData['rest_between_rounds'] ?? null,
                        'time_cap' => $blockData['time_cap'] ?? null,
                        'work_interval' => $blockData['work_interval'] ?? null,
                        'rest_interval' => $blockData['rest_interval'] ?? null,
                        'notes' => $blockData['notes'] ?? null,
                    ]);

                    foreach ($blockData['exercises'] ?? [] as $exerciseData) {
                        $exerciseable = $this->createExerciseable($exerciseData);

                        $block->exercises()->create([
                            'name' => $exerciseData['name'],
                            'order' => $exerciseData['order'],
                            'exerciseable_type' => $exerciseable->getMorphClass(),
                            'exerciseable_id' => $exerciseable->getKey(),
                            'notes' => $exerciseData['notes'] ?? null,
                        ]);
                    }
                }
            }

            return $workout->load('sections.blocks.exercises.exerciseable');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createExerciseable(array $data): StrengthExercise|CardioExercise|DurationExercise
    {
        return match ($data['type']) {
            'strength' => StrengthExercise::create([
                'target_sets' => $data['target_sets'] ?? null,
                'target_reps_min' => $data['target_reps_min'] ?? null,
                'target_reps_max' => $data['target_reps_max'] ?? null,
                'target_weight' => $data['target_weight'] ?? null,
                'target_rpe' => $data['target_rpe'] ?? null,
                'target_tempo' => $data['target_tempo'] ?? null,
                'rest_after' => $data['rest_after'] ?? null,
            ]),
            'cardio' => CardioExercise::create([
                'target_duration' => $data['target_duration'] ?? null,
                'target_distance' => $data['target_distance'] ?? null,
                'target_pace_min' => $data['target_pace_min'] ?? null,
                'target_pace_max' => $data['target_pace_max'] ?? null,
                'target_heart_rate_zone' => $data['target_heart_rate_zone'] ?? null,
                'target_heart_rate_min' => $data['target_heart_rate_min'] ?? null,
                'target_heart_rate_max' => $data['target_heart_rate_max'] ?? null,
                'target_power' => $data['target_power'] ?? null,
            ]),
            'duration' => DurationExercise::create([
                'target_duration' => $data['target_duration'] ?? null,
                'target_rpe' => $data['target_rpe'] ?? null,
            ]),
        };
    }
}
