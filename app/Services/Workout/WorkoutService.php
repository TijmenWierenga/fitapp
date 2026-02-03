<?php

declare(strict_types=1);

namespace App\Services\Workout;

use App\Data\CompleteWorkoutData;
use App\Data\CreateWorkoutData;
use App\Data\UpdateWorkoutData;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutInjuryEvaluation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkoutService
{
    public function create(User $user, CreateWorkoutData $data): Workout
    {
        return Workout::create([
            'user_id' => $user->id,
            'name' => $data->name,
            'activity' => $data->activity,
            'scheduled_at' => $data->scheduledAt,
            'notes' => $data->notes,
        ]);
    }

    public function update(User $user, Workout $workout, UpdateWorkoutData $data): Workout
    {
        Gate::forUser($user)->authorize('update', $workout);

        $updateData = [];

        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }

        if ($data->activity !== null) {
            $updateData['activity'] = $data->activity;
        }

        if ($data->scheduledAt !== null) {
            $updateData['scheduled_at'] = $data->scheduledAt;
        }

        if ($data->updateNotes) {
            $updateData['notes'] = $data->notes;
        }

        $workout->update($updateData);

        return $workout;
    }

    public function delete(User $user, Workout $workout): bool
    {
        Gate::forUser($user)->authorize('delete', $workout);

        return (bool) $workout->delete();
    }

    public function complete(User $user, Workout $workout, CompleteWorkoutData $data): Workout
    {
        Gate::forUser($user)->authorize('complete', $workout);

        return DB::transaction(function () use ($user, $workout, $data): Workout {
            $workout->markAsCompleted($data->rpe, $data->feeling, $data->completionNotes);

            foreach ($data->injuryEvaluations as $evaluation) {
                $injury = $user->injuries()->find($evaluation->injuryId);

                if ($injury === null) {
                    continue;
                }

                WorkoutInjuryEvaluation::create([
                    'workout_id' => $workout->id,
                    'injury_id' => $evaluation->injuryId,
                    'discomfort_score' => $evaluation->discomfortScore,
                    'notes' => $evaluation->notes,
                ]);
            }

            $workout->refresh();
            $workout->load('injuryEvaluations.injury');

            return $workout;
        });
    }

    public function find(User $user, int $workoutId): ?Workout
    {
        $workout = Workout::where('user_id', $user->id)->find($workoutId);

        if ($workout) {
            Gate::forUser($user)->authorize('view', $workout);
        }

        return $workout;
    }

    /**
     * @return Collection<int, Workout>
     */
    public function list(User $user, string $filter = 'all', int $limit = 20): Collection
    {
        $query = $user->workouts();

        match ($filter) {
            'upcoming' => $query->upcoming(),
            'completed' => $query->completed(),
            'overdue' => $query->overdue(),
            default => $query->orderBy('scheduled_at', 'desc'),
        };

        return $query->limit($limit)->get();
    }
}
