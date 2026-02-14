<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\UserProfileResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetUserProfileTool extends Tool
{
    public function __construct(
        private UserProfileResource $resource,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's profile information including name, email, timezone, and initials.

        Use this to personalize responses or determine the user's locale. Returns the same data as the `user://profile` resource.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return $this->resource->handle($request);
    }
}
