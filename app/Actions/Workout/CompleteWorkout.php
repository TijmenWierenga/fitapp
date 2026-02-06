<?php

declare(strict_types=1);

namespace App\Actions\Workout;

use App\Models\Workout;
use App\Models\WorkoutMuscleLoadSnapshot;
use App\Services\MuscleLoad\MuscleLoadCalculator;

class CompleteWorkout
{
    public function __construct(private MuscleLoadCalculator $calculator) {}

    /**
     * Mark a workout as completed and create muscle load snapshots.
     */
    public function execute(Workout $workout, int $rpe, int $feeling): void
    {
        $workout->markAsCompleted($rpe, $feeling);

        $summary = $this->calculator->calculate($workout);

        $completedAt = $workout->completed_at;

        foreach ($summary->toSnapshotData() as $snapshotData) {
            WorkoutMuscleLoadSnapshot::create([
                'workout_id' => $workout->id,
                'muscle_group' => $snapshotData['muscle_group'],
                'total_load' => $snapshotData['total_load'],
                'source_breakdown' => $snapshotData['source_breakdown'],
                'completed_at' => $completedAt,
            ]);
        }
    }
}
