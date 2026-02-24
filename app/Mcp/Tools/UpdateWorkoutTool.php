<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\UpdateWorkoutHandler;
use App\Tools\Input\UpdateWorkoutInput;
use App\Tools\WorkoutSchemaBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
class UpdateWorkoutTool extends Tool
{
    public function __construct(
        private UpdateWorkoutHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing workout.

        You can update the name, activity, scheduled time, notes, or sections. Only provide the fields you want to change.

        If `sections` is provided, the existing structure will be replaced entirely with the new sections/blocks/exercises. See the `block_type` schema for field guidance per type.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'activity' => ['sometimes', Rule::enum(\App\Enums\Workout\Activity::class)],
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string|max:5000',
            ...WorkoutSchemaBuilder::sectionValidationRules(),
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            UpdateWorkoutInput::fromArray($validated),
        );

        return $result->failed()
            ? Response::error($result->errorMessage())
            : Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }
}
