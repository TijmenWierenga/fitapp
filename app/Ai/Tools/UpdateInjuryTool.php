<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\UpdateInjuryHandler;
use App\Tools\Input\UpdateInjuryInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateInjuryTool implements Tool
{
    public function __construct(
        private UpdateInjuryHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Update an existing injury record. Use this to mark an injury as resolved (set ended_at), reopen it (set ended_at to null), or update details. Only provide the fields you want to change.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            UpdateInjuryInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
