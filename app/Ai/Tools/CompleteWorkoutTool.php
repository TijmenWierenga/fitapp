<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\CompleteWorkoutHandler;
use App\Tools\Input\CompleteWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CompleteWorkoutTool implements Tool
{
    public function __construct(
        private CompleteWorkoutHandler $handler,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Mark a workout as completed with RPE and feeling ratings, and optional pain scores for active injuries.

        RPE (Rate of Perceived Exertion): 1-10 scale (1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum).
        Feeling: 1-5 scale (1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great).
        Pain Scores: 0-10 NRS scale per active injury (0=No Pain, 1-3=Mild, 4-6=Moderate, 7-10=Severe).

        When the user has active injuries, always ask about pain levels during workout completion.
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
            CompleteWorkoutInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
