<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\User;
use App\Tools\Input\DeleteWorkoutInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class DeleteWorkoutHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to delete'),
        ];
    }

    public function execute(User $user, DeleteWorkoutInput $input): ToolResult
    {
        $workout = $user->workouts()->find($input->workoutId);

        if (! $workout) {
            return ToolResult::error('Workout not found or access denied.');
        }

        if ($user->cannot('delete', $workout)) {
            return ToolResult::error('You do not have permission to delete this workout.');
        }

        $workout->delete();

        return ToolResult::success(['message' => 'Workout deleted successfully']);
    }
}
