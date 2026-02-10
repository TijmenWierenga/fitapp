<?php

namespace App\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Fetch a single workout by ID. Returns full workout details including sections, blocks, exercises, RPE and feeling if completed.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            Gate::forUser($user)->authorize('view', $workout);
        } catch (AuthorizationException) {
            return Response::error('Workout not found or access denied');
        }

        return Response::text(json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout, $user),
        ]));
    }

    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
    ) {}

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to fetch'),
        ];
    }
}
