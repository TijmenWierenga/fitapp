<?php

namespace App\Ai\Tools;

use App\Actions\BuildCoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class RefreshUserContextTool implements Tool
{
    public function __construct(
        private BuildCoachContext $buildCoachContext,
    ) {}

    public function description(): string
    {
        return 'Refresh the user context (date/time, fitness profile, workload, injuries, schedule). Use this after making changes (e.g., creating a workout) to get updated data.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        return $this->buildCoachContext->execute(auth()->user());
    }
}
