<?php

namespace App\Ai\Tools;

use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ListWorkoutsTool implements Tool
{
    public function description(): string
    {
        return 'List workouts with optional filtering. Filters: upcoming, completed, overdue, all (default). Max 100 results.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->enum(['upcoming', 'completed', 'overdue', 'all'])->description('Filter workouts (default: all).')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of workouts to return (default: 20, max: 100)')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $filter = $request['filter'] ?? 'all';
        $limit = min($request['limit'] ?? 20, 100);

        $query = $user->workouts()->withCount('sections');

        match ($filter) {
            'upcoming' => $query->upcoming(),
            'completed' => $query->completed(),
            'overdue' => $query->overdue(),
            default => $query->orderBy('scheduled_at', 'desc'),
        };

        $workouts = $query->limit($limit)->get();

        $workoutData = $workouts->map(fn (Workout $workout): array => [
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
            'completed' => $workout->isCompleted(),
            'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->toIso8601String() : null,
            'notes' => $workout->notes,
            'sections_count' => $workout->sections_count,
        ]);

        return json_encode([
            'success' => true,
            'filter' => $filter,
            'count' => $workoutData->count(),
            'workouts' => $workoutData->toArray(),
        ]);
    }
}
