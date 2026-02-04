<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class UserProfileResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'user://profile';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only user identity information including name, email, timezone, and initials.

        Use URI: user://profile
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $content = $this->buildProfileContent($user);

        return Response::text($content);
    }

    protected function buildProfileContent(User $user): string
    {
        return <<<TEXT
        # User Profile

        **Name:** {$user->name}
        **Email:** {$user->email}
        **Timezone:** {$user->timezone}
        **Initials:** {$user->initials()}

        TEXT;
    }
}
