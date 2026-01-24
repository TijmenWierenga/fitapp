<?php

namespace App\Mcp\Tools;

use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Deletes a workout if allowed. Completed workouts and past workouts (except today) cannot be deleted.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error('Authentication required. Please provide a valid API token.');
        }

        $validator = Validator::make($request->all(), [
            'workout_id' => ['required', 'integer', 'exists:workouts,id'],
        ]);

        if ($validator->fails()) {
            return Response::error('Validation failed: '.json_encode($validator->errors()->toArray()));
        }

        $validated = $validator->validated();

        $workout = Workout::find($validated['workout_id']);

        if ($workout->user_id !== $user->id) {
            return Response::error('You do not have permission to delete this workout.');
        }

        $deleted = $workout->deleteIfAllowed();

        if (! $deleted) {
            return Response::error('This workout cannot be deleted. Completed workouts and past workouts (except today) cannot be deleted.');
        }

        return Response::json([
            'success' => true,
            'message' => 'Workout deleted successfully.',
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()
                ->description('The ID of the workout to delete'),
        ];
    }
}
