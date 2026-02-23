<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\CompleteWorkoutHandler;
use App\Tools\Input\CompleteWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CompleteWorkoutTool extends Tool
{
    public function __construct(
        private CompleteWorkoutHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Mark a workout as completed with RPE and feeling ratings.

        **RPE (Rate of Perceived Exertion):** 1-10 scale
        - 1-2: Very Easy
        - 3-4: Easy
        - 5-6: Moderate
        - 7-8: Hard
        - 9-10: Maximum Effort

        **Feeling:** 1-5 scale (post-workout feeling)
        - 1: Terrible
        - 2: Poor
        - 3: Average
        - 4: Good
        - 5: Great
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'rpe' => 'required|integer|min:1|max:10',
            'feeling' => 'required|integer|min:1|max:5',
        ], [
            'rpe.min' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'rpe.max' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'feeling.min' => 'Feeling must be between 1 and 5',
            'feeling.max' => 'Feeling must be between 1 and 5',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            CompleteWorkoutInput::fromArray($validated),
        );

        return $result->failed()
            ? Response::error($result->errorMessage())
            : Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }
}
