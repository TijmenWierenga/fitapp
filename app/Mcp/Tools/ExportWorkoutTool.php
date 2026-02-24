<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\ExportWorkoutHandler;
use App\Tools\Input\ExportWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ExportWorkoutTool extends Tool
{
    public function __construct(
        private ExportWorkoutHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Export a workout as a Garmin FIT file. Returns base64-encoded binary data that can be imported into Garmin Connect or other FIT-compatible tools.
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
            ExportWorkoutInput::fromArray($validated),
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
