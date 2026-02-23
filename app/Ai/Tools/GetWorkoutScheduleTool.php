<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\GetWorkoutScheduleHandler;
use App\Tools\Input\GetWorkoutScheduleInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkoutScheduleTool implements Tool
{
    public function __construct(
        private GetWorkoutScheduleHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Get the user\'s workout schedule showing upcoming and recently completed workouts. Use this to understand what the user has planned and recently done before suggesting new workouts.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            GetWorkoutScheduleInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
