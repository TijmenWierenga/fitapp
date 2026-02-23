<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\GetFitnessProfileHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetFitnessProfileTool implements Tool
{
    public function __construct(
        private GetFitnessProfileHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Get the user\'s fitness profile including primary goal, goal details, available training days, and session duration preferences.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(auth()->user());

        return json_encode($result->toArray());
    }
}
