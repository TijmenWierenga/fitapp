<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\DeleteWorkoutHandler;
use App\Tools\Input\DeleteWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class DeleteWorkoutTool implements Tool
{
    public function __construct(
        private DeleteWorkoutHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Delete a workout when it is no longer needed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            DeleteWorkoutInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
