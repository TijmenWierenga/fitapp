<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Fetch a single workout by ID. Returns full workout details including RPE and feeling if completed.
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

        $data = [
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
            'completed' => $workout->isCompleted(),
            'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->toIso8601String() : null,
            'rpe' => $workout->rpe,
            'rpe_label' => Workout::getRpeLabel($workout->rpe),
            'feeling' => $workout->feeling,
            'notes' => $workout->notes,
        ];

        return Response::text(json_encode([
            'success' => true,
            'workout' => $data,
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
            'workout_id' => $schema->integer()->description('The ID of the workout to fetch'),
        ];
    }
}
