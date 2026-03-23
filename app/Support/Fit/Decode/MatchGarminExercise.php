<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\Models\Exercise;

class MatchGarminExercise
{
    public function match(int $garminCategory, int $garminName): ?Exercise
    {
        return Exercise::query()
            ->where('garmin_exercise_category', $garminCategory)
            ->where('garmin_exercise_name', $garminName)
            ->first();
    }
}
