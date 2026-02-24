<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\ListWorkoutsHandler;
use App\Tools\Input\ListWorkoutsInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListWorkoutsTool extends Tool
{
    public function __construct(
        private ListWorkoutsHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List workouts for a user with optional filtering.

        **Filters:**
        - `upcoming`: Workouts scheduled in the future (not completed)
        - `completed`: Workouts that have been completed
        - `overdue`: Workouts scheduled in the past but not completed
        - `all`: All workouts (default)

        Results are limited to 20 by default, max 100.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'filter' => 'nullable|in:upcoming,completed,overdue,all',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            ListWorkoutsInput::fromArray($validated),
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
