<?php

namespace App\Mcp\Tools;

use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListWorkoutsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lists workouts for the authenticated user with optional filters (upcoming, completed, overdue, or all). Returns workouts with all details including notes.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error('Authentication required. Please provide a valid API token.');
        }

        $validator = Validator::make($request->all(), [
            'filter' => ['sometimes', 'string', 'in:upcoming,completed,overdue,all'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return Response::error('Validation failed: '.json_encode($validator->errors()->toArray()));
        }

        $validated = $validator->validated();
        $filter = $validated['filter'] ?? 'all';
        $limit = $validated['limit'] ?? 10;

        $query = Workout::query()->where('user_id', $user->id);

        match ($filter) {
            'upcoming' => $query->upcoming(),
            'completed' => $query->completed(),
            'overdue' => $query->overdue(),
            default => null,
        };

        $workouts = $query->orderBy('scheduled_at', 'asc')->limit($limit)->get();

        $workoutsData = $workouts->map(function (Workout $workout) use ($user) {
            return [
                'id' => $workout->id,
                'name' => $workout->name,
                'sport' => $workout->sport->value,
                'notes' => $workout->notes,
                'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->format('Y-m-d H:i:s'),
                'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->format('Y-m-d H:i:s') : null,
                'is_completed' => $workout->is_completed,
            ];
        });

        return Response::json([
            'workouts' => $workoutsData->toArray(),
            'count' => $workouts->count(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->enum(['upcoming', 'completed', 'overdue', 'all'])
                ->description('Filter workouts by status: upcoming (future workouts), completed (finished workouts), overdue (past but not completed), or all (no filter)')
                ->optional(),
            'limit' => $schema->integer()
                ->description('Maximum number of workouts to return (1-100, default: 10)')
                ->optional(),
        ];
    }
}
