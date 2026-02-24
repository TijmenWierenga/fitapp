<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\CreateWorkoutHandler;
use App\Tools\Input\CreateWorkoutInput;
use App\Tools\WorkoutSchemaBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class CreateWorkoutTool extends Tool
{
    public function __construct(
        private CreateWorkoutHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new workout with a scheduled date and time. You MUST include structured sections with blocks and exercises — every workout needs at least one section, each section needs at least one block, and each block needs at least one exercise.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities.

        Dates/times should be in the user's local timezone and will be converted to UTC for storage.

        The `sections` array defines the workout structure. Each section contains blocks, and each block contains exercises with a `type` field (strength, cardio, or duration) plus type-specific fields. See the `block_type` schema for field guidance per type.

        IMPORTANT: Before creating a workout, use `SearchExercisesTool` to find exercises in the catalog and include their `exercise_id` in each block exercise. This links exercises to the catalog for muscle group tracking and Garmin compatibility.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'activity' => ['required', Rule::enum(\App\Enums\Workout\Activity::class)],
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
            ...WorkoutSchemaBuilder::sectionValidationRules(),
            'sections' => 'required|array|min:1',
            'sections.*.blocks' => 'required|array|min:1',
            'sections.*.blocks.*.exercises' => 'required|array|min:1',
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            CreateWorkoutInput::fromArray($validated),
        );

        return Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }
}
