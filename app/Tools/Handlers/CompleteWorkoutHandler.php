<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\User;
use App\Tools\Input\CompleteWorkoutInput;
use App\Tools\ToolResult;
use App\Tools\WorkoutResponseFormatter;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CompleteWorkoutHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to complete'),
            'rpe' => $schema->integer()->description('Rate of Perceived Exertion (1-10): 1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum'),
            'feeling' => $schema->integer()->description('Post-workout feeling (1-5): 1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great'),
        ];
    }

    public function execute(User $user, CompleteWorkoutInput $input): ToolResult
    {
        $workout = $user->workouts()->find($input->workoutId);

        if (! $workout) {
            return ToolResult::error('Workout not found or access denied.');
        }

        if ($user->cannot('complete', $workout)) {
            return ToolResult::error('Cannot complete an already completed workout.');
        }

        $workout->markAsCompleted($input->rpe, $input->feeling);

        return ToolResult::success([
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout completed successfully',
        ]);
    }
}
