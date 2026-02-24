<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\User;
use App\Tools\Input\GetWorkoutInput;
use App\Tools\ToolResult;
use App\Tools\WorkoutResponseFormatter;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class GetWorkoutHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to fetch'),
        ];
    }

    public function execute(User $user, GetWorkoutInput $input): ToolResult
    {
        $workout = $user->workouts()->find($input->workoutId);

        if (! $workout) {
            return ToolResult::error('Workout not found or access denied.');
        }

        return ToolResult::success([
            'workout' => WorkoutResponseFormatter::format($workout, $user),
        ]);
    }
}
