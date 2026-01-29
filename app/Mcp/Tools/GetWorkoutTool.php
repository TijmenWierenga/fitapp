<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesUser;
use App\Models\Workout;
use App\Services\Workout\WorkoutService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetWorkoutTool extends Tool
{
    use ResolvesUser;

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Fetch a single workout by ID. Returns full workout details including RPE and feeling if completed.
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
            'user_id' => 'nullable|integer|exists:users,id',
            'workout_id' => 'required|integer',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
        ]);

        $user = $this->resolveUser($request);

        $workout = $this->workoutService->find($user, $validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        $data = [
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
            'completed' => $workout->isCompleted(),
            'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->toIso8601String() : null,
            'rpe' => $workout->rpe,
            'rpe_label' => Workout::getRpeLabel($workout->rpe),
            'feeling' => $workout->feeling,
            'notes' => $workout->notes,
        ];

        return Response::text(json_encode([
            'success' => true,
            'workout' => $data,
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
            'user_id' => $schema->integer()->description('User ID (required for local MCP, ignored for authenticated web requests)')->nullable(),
            'workout_id' => $schema->integer()->description('The ID of the workout to fetch'),
        ];
    }
}
