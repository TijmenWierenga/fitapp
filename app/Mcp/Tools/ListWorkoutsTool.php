<?php

namespace App\Mcp\Tools;

use App\Services\Workout\WorkoutService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

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

    public function __construct(
        protected WorkoutService $workoutService
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'filter' => 'nullable|in:upcoming,completed,overdue,all',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();

        $filter = $validated['filter'] ?? 'all';
        $limit = $validated['limit'] ?? 20;

        $workouts = $this->workoutService->list($user, $filter, $limit);

        $workoutData = $workouts->map(function ($workout) use ($user) {
            return [
                'id' => $workout->id,
                'name' => $workout->name,
                'activity' => $workout->activity->value,
                'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
                'completed' => $workout->isCompleted(),
                'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->toIso8601String() : null,
                'notes' => $workout->notes,
            ];
        });

        return Response::text(json_encode([
            'success' => true,
            'filter' => $filter,
            'count' => $workoutData->count(),
            'workouts' => $workoutData->toArray(),
        ]));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->description('Filter workouts: upcoming, completed, overdue, or all (default)')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of workouts to return (default: 20, max: 100)')->nullable(),
        ];
    }
}
