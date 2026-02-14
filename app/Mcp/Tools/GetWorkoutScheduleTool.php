<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\WorkoutScheduleResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetWorkoutScheduleTool extends Tool
{
    public function __construct(
        private WorkoutScheduleResource $resource,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's workout schedule showing upcoming and recently completed workouts.

        Use this to understand what the user has planned and recently done before suggesting new workouts. Returns the same data as the `workout://schedule` resource.
    MARKDOWN;

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'upcoming_limit' => $schema->integer()->description('Number of upcoming workouts to return (default: 20, max: 50)'),
            'completed_limit' => $schema->integer()->description('Number of completed workouts to return (default: 10, max: 50)'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return $this->resource->handle($request);
    }
}
