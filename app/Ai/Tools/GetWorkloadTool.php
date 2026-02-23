<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\GetWorkloadHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkloadTool implements Tool
{
    public function __construct(
        private GetWorkloadHandler $handler,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Get the user's training workload data based on completed workouts from up to 56 days.

        Returns session load (sRPE with monotony and strain), muscle group volume (weekly sets with 4-week average and trend), and strength progression (estimated 1RM changes).

        Use this data to monitor training load, balance volume across muscle groups, and track strength progression.
        TEXT;
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
