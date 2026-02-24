<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\AddInjuryHandler;
use App\Tools\Input\AddInjuryInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class AddInjuryTool implements Tool
{
    public function __construct(
        private AddInjuryHandler $handler,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Add an injury record. Track current or past injuries that should be considered when creating workout plans.

        Injury Types: acute, chronic, recurring, post_surgery.
        Body Parts: shoulder, chest, biceps, triceps, forearm, wrist, hand, elbow, neck, upper_back, lower_back, core, ribs, hip, glutes, quadriceps, hamstring, knee, calf, ankle, foot, other.
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
            AddInjuryInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
