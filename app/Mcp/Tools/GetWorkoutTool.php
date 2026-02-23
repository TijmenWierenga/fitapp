<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\GetWorkoutHandler;
use App\Tools\Input\GetWorkoutInput;
use App\Tools\WorkoutSchemaBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetWorkoutTool extends Tool
{
    public function __construct(
        private GetWorkoutHandler $handler,
        private WorkoutSchemaBuilder $schemaBuilder,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Fetch a single workout by ID. Returns full workout details including sections, blocks, exercises, RPE and feeling if completed.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            GetWorkoutInput::fromArray($validated),
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
