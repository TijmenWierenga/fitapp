<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
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

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        if ($user->cannot('delete', $workout)) {
            if ($workout->isCompleted()) {
                return Response::error('Cannot delete completed workouts');
            }

            return Response::error('Cannot delete past workouts (except today)');
        }

        $workout->delete();

        return Response::text(json_encode([
            'success' => true,
            'message' => 'Workout deleted successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to delete'),
        ];
    }
}
