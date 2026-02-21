<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class DeleteWorkoutTool implements Tool
{
    public function description(): string
    {
        return 'Delete a workout when it is no longer needed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to delete'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $workout = $user->workouts()->find($request['workout_id']);

        if (! $workout) {
            return json_encode(['error' => 'Workout not found or access denied.']);
        }

        if ($user->cannot('delete', $workout)) {
            return json_encode(['error' => 'You do not have permission to delete this workout.']);
        }

        $workout->delete();

        return json_encode([
            'success' => true,
            'message' => 'Workout deleted successfully',
        ]);
    }
}
