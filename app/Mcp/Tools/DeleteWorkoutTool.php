<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\Workout;
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

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'workout_id' => 'required|integer',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $workout = Workout::where('user_id', $user->id)->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        if ($workout->isCompleted()) {
            return Response::error('Cannot delete completed workouts');
        }

        if ($workout->scheduled_at->isPast() && ! $workout->scheduled_at->isToday()) {
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
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user who owns the workout'),
            'workout_id' => $schema->integer()->description('The ID of the workout to delete'),
        ];
    }
}
