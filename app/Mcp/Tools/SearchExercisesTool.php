<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\SearchExercisesHandler;
use App\Tools\Input\SearchExercisesInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchExercisesTool extends Tool
{
    public function __construct(
        private SearchExercisesHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Search and filter the exercise catalog. Returns exercises with their muscle group mappings so you can pick appropriate `exercise_id` values for structured workouts.

        At least one of `query` or `muscle_group` is required.

        **Usage tips:**
        - Use `query` to search by exercise name (e.g., "bench press", "squat")
        - Use `muscle_group` to find exercises targeting a specific muscle — read the `exercise://muscle-groups` resource for valid values
        - Combine filters for precise results (e.g., query="press" + equipment="dumbbell")
        - Primary muscles (load_factor 1.0) receive full training volume; secondary (0.5) receive half
        - Use `garmin_compatible: true` to only return exercises with Garmin FIT exercise mappings (recommended when the user has `prefer_garmin_exercises` enabled)
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'queries' => 'nullable|array|max:10',
            'queries.*' => 'string|max:255',
            'muscle_group' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:strength,stretching,plyometrics,cardio',
            'equipment' => 'nullable|string|max:255',
            'level' => 'nullable|string|in:beginner,intermediate,expert',
            'garmin_compatible' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $result = $this->handler->execute(
            SearchExercisesInput::fromArray($validated),
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
