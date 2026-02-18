<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CompleteWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Mark a workout as completed with RPE, feeling ratings, and duration.

        **Duration:** Total workout time in seconds (e.g., 3600 for 1 hour).

        **RPE (Rate of Perceived Exertion):** 1-10 scale
        - 1-2: Very Easy
        - 3-4: Easy
        - 5-6: Moderate
        - 7-8: Hard
        - 9-10: Maximum Effort

        **Feeling:** 1-5 scale (post-workout feeling)
        - 1: Terrible
        - 2: Poor
        - 3: Average
        - 4: Good
        - 5: Great
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'duration' => 'required|integer|min:60',
            'rpe' => 'required|integer|min:1|max:10',
            'feeling' => 'required|integer|min:1|max:5',
        ], [
            'duration.min' => 'Duration must be at least 60 seconds (1 minute)',
            'rpe.min' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'rpe.max' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'feeling.min' => 'Feeling must be between 1 and 5',
            'feeling.max' => 'Feeling must be between 1 and 5',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied.');
        }

        if ($user->cannot('complete', $workout)) {
            return Response::error('Cannot complete an already completed workout.');
        }

        $workout->markAsCompleted($validated['rpe'], $validated['feeling'], $validated['duration']);

        return Response::structured([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout completed successfully',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to complete'),
            'duration' => $schema->integer()->description('Total workout duration in seconds (e.g., 3600 for 1 hour)'),
            'rpe' => $schema->integer()->description('Rate of Perceived Exertion (1-10): 1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum'),
            'feeling' => $schema->integer()->description('Post-workout feeling (1-5): 1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great'),
        ];
    }
}
