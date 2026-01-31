<?php

namespace App\Mcp\Tools;

use App\Services\Workout\WorkoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a workout. Business rules:
        - Cannot delete completed workouts
        - Cannot delete past workouts (except today's workouts)
    MARKDOWN;

    public function __construct(
        private WorkoutService $workoutService
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
        ]);

        $user = $request->user();

        $workout = $this->workoutService->find($user, $validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            $this->workoutService->delete($user, $workout);
        } catch (AuthorizationException) {
            if ($workout->isCompleted()) {
                return Response::error('Cannot delete completed workouts');
            }

            return Response::error('Cannot delete past workouts (except today)');
        }

        return Response::text(json_encode([
            'success' => true,
            'message' => 'Workout deleted successfully',
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
            'workout_id' => $schema->integer()->description('The ID of the workout to delete'),
        ];
    }
}
