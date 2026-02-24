<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\SearchExercisesHandler;
use App\Tools\Input\SearchExercisesInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchExercisesTool implements Tool
{
    public function __construct(
        private SearchExercisesHandler $handler,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Search and filter the exercise catalog. Returns exercises with muscle group mappings so you can pick appropriate exercise_id values for structured workouts.

        At least one of query or muscle_group is required. Use query to search by name, muscle_group to filter by target muscle. Combine filters for precise results.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            SearchExercisesInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
