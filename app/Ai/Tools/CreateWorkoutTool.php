<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\CreateWorkoutHandler;
use App\Tools\Input\CreateWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CreateWorkoutTool implements Tool
{
    public function __construct(
        private CreateWorkoutHandler $handler,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Create a new workout with a scheduled date and time. You MUST include structured sections with blocks and exercises — every workout needs at least one section, each section needs at least one block, and each block needs at least one exercise.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities.

        Dates/times should be in the user's local timezone. The `sections` array defines the workout structure with warm-up, main work, and cool-down sections.

        IMPORTANT: Before creating a workout, use `SearchExercisesTool` to find exercises in the catalog and include their `exercise_id` in each block exercise. This links exercises to the catalog for muscle group tracking and Garmin compatibility.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            CreateWorkoutInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
