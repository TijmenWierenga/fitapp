<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\GetWorkoutHandler;
use App\Tools\Input\GetWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkoutTool implements Tool
{
    public function __construct(
        private GetWorkoutHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Fetch a single workout by ID. Returns full workout details including sections, blocks, exercises, RPE and feeling if completed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            GetWorkoutInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
