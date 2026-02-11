<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetUserProfileTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's profile information including name, email, timezone, and initials.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $content = <<<TEXT
        # User Profile

        **Name:** {$user->name}
        **Email:** {$user->email}
        **Timezone:** {$user->timezone}
        **Initials:** {$user->initials()}

        TEXT;

        return Response::text($content);
    }
}
