<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\ListWorkoutsHandler;
use App\Tools\Input\ListWorkoutsInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ListWorkoutsTool implements Tool
{
    public function __construct(
        private ListWorkoutsHandler $handler,
    ) {}

    public function description(): string
    {
        return 'List workouts with optional filtering. Filters: upcoming, completed, overdue, all (default). Max 100 results.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            ListWorkoutsInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
