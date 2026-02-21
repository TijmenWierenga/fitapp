<?php

namespace App\Ai\Tools;

use App\Mcp\Tools\WorkoutResponseFormatter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CompleteWorkoutTool implements Tool
{
    public function description(): string
    {
        return <<<'TEXT'
        Mark a workout as completed with RPE and feeling ratings.

        RPE (Rate of Perceived Exertion): 1-10 scale (1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum).
        Feeling: 1-5 scale (1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great).
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to complete'),
            'rpe' => $schema->integer()->description('Rate of Perceived Exertion (1-10)'),
            'feeling' => $schema->integer()->description('Post-workout feeling (1-5)'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $workout = $user->workouts()->find($request['workout_id']);

        if (! $workout) {
            return json_encode(['error' => 'Workout not found or access denied.']);
        }

        if ($user->cannot('complete', $workout)) {
            return json_encode(['error' => 'Cannot complete an already completed workout.']);
        }

        $workout->markAsCompleted($request['rpe'], $request['feeling']);

        return json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout completed successfully',
        ]);
    }
}
