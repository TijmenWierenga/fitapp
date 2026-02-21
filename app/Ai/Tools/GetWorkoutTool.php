<?php

namespace App\Ai\Tools;

use App\Mcp\Tools\WorkoutResponseFormatter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkoutTool implements Tool
{
    public function description(): string
    {
        return 'Fetch a single workout by ID. Returns full workout details including sections, blocks, exercises, RPE and feeling if completed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to fetch'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $workout = $user->workouts()->find($request['workout_id']);

        if (! $workout) {
            return json_encode(['error' => 'Workout not found or access denied.']);
        }

        return json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout, $user),
        ]);
    }
}
