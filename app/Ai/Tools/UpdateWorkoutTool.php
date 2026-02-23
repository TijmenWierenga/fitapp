<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\UpdateWorkoutHandler;
use App\Tools\Input\UpdateWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateWorkoutTool implements Tool
{
    public function __construct(
        private UpdateWorkoutHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Update an existing workout. You can update the name, activity, scheduled time, notes, or sections. If sections is provided, the existing structure will be replaced entirely.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            UpdateWorkoutInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
