<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\UserFitnessProfileResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetFitnessProfileTool extends Tool
{
    public function __construct(
        private UserFitnessProfileResource $resource,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's fitness profile including primary goal, goal details, available training days, and session duration preferences.

        Use this before creating workout plans to respect the user's goals and schedule. Returns the same data as the `user://fitness-profile` resource.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return $this->resource->handle($request);
    }
}
