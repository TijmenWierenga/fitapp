<?php

namespace App\Actions;

use App\Models\Workout;
use App\Models\WorkoutInjuryPainScore;

class RecordPainScores
{
    /**
     * @param  array<int, int>  $scores  Map of injury_id => pain_score
     */
    public function execute(Workout $workout, array $scores): void
    {
        foreach ($scores as $injuryId => $painScore) {
            WorkoutInjuryPainScore::create([
                'workout_id' => $workout->id,
                'injury_id' => $injuryId,
                'pain_score' => $painScore,
            ]);
        }
    }
}
