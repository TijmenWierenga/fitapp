<?php

namespace App\Actions;

use App\DataTransferObjects\Workout\PainScore;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

class CompleteWorkout
{
    public function execute(User $user, Workout $workout, int $rpe, int $feeling, PainScore ...$painScores): void
    {
        $activeInjuryIds = $user->activeInjuries()->pluck('id');

        DB::transaction(function () use ($workout, $rpe, $feeling, $painScores, $activeInjuryIds): void {
            $workout->markAsCompleted($rpe, $feeling);

            foreach ($painScores as $painScore) {
                if (! $activeInjuryIds->contains($painScore->injuryId)) {
                    continue;
                }

                $workout->painScores()->create([
                    'injury_id' => $painScore->injuryId,
                    'pain_score' => $painScore->painScore,
                ]);
            }
        });
    }
}
