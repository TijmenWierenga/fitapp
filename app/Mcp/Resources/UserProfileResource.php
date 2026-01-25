<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class UserProfileResource extends Resource implements HasUriTemplate
{
    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only user profile information including name, email, timezone, and initials.

        Use URI template: user://profile/{userId}
    MARKDOWN;

    /**
     * Get the URI template for this resource.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('user://profile/{userId}');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $userId = $request->get('userId');

        if (! $userId) {
            return Response::error('User ID is required');
        }

        $user = User::find($userId);

        if (! $user) {
            return Response::error('User not found');
        }

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
