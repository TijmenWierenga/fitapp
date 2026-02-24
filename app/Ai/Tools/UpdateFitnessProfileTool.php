<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\UpdateFitnessProfileHandler;
use App\Tools\Input\UpdateFitnessProfileInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateFitnessProfileTool implements Tool
{
    public function __construct(
        private UpdateFitnessProfileHandler $handler,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Update or create the user's fitness profile. Set their fitness goals, available training days, and session duration preferences.

        Primary Goals: weight_loss, muscle_gain, endurance, general_fitness.
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
            UpdateFitnessProfileInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
