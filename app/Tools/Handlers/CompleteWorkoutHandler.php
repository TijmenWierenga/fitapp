<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Actions\CompleteWorkout;
use App\Models\User;
use App\Tools\Input\CompleteWorkoutInput;
use App\Tools\ToolResult;
use App\Tools\WorkoutResponseFormatter;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CompleteWorkoutHandler
{
    public function __construct(
        private CompleteWorkout $completeWorkout,
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to complete'),
            'rpe' => $schema->integer()->description('Rate of Perceived Exertion (1-10): 1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum'),
            'feeling' => $schema->integer()->description('Post-workout feeling (1-5): 1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great'),
            'pain_scores' => $schema->array()->nullable()->description('Optional pain scores for active injuries. Each entry: {injury_id, pain_score (0-10)}')->items(
                $schema->object([
                    'injury_id' => $schema->integer()->description('The ID of the active injury'),
                    'pain_score' => $schema->integer()->description('Pain score (0-10): 0=No Pain, 1-3=Mild, 4-6=Moderate, 7-10=Severe'),
                ]),
            ),
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

        $this->completeWorkout->execute($user, $workout, $input->rpe, $input->feeling, ...$input->painScores);

        return ToolResult::success([
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout completed successfully',
        ]);
    }
}
