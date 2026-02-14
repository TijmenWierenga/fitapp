<?php

namespace App\Mcp\Tools;

use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListWorkoutsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List workouts for a user with optional filtering.

        **Filters:**
        - `upcoming`: Workouts scheduled in the future (not completed)
        - `completed`: Workouts that have been completed
        - `overdue`: Workouts scheduled in the past but not completed
        - `all`: All workouts (default)

        Results are limited to 20 by default, max 100.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'filter' => 'nullable|in:upcoming,completed,overdue,all',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();

        $filter = $validated['filter'] ?? 'all';
        $limit = $validated['limit'] ?? 20;

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

        return Response::structured([
            'success' => true,
            'filter' => $filter,
            'count' => $workoutData->count(),
            'workouts' => $workoutData->toArray(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->enum(['upcoming', 'completed', 'overdue', 'all'])->description('Filter workouts (default: all).')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of workouts to return (default: 20, max: 100)')->nullable(),
        ];
    }
}
