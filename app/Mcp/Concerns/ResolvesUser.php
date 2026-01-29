<?php

declare(strict_types=1);

namespace App\Mcp\Concerns;

use App\Models\User;
use InvalidArgumentException;
use Laravel\Mcp\Request;

trait ResolvesUser
{
    /**
     * Resolve the user from the MCP request.
     *
     * For web requests (Sanctum authenticated), uses the authenticated user.
     * For local MCP requests, uses the user_id parameter.
     *
     * @throws InvalidArgumentException When no user can be resolved
     */
    protected function resolveUser(Request $request): User
    {
        // Web: Use authenticated user from Sanctum
        if ($authenticatedUser = $request->user()) {
            return $authenticatedUser;
        }

        // Local: Use user_id parameter
        $userId = $request->get('user_id');

        if (! $userId) {
            throw new InvalidArgumentException('User authentication required. For local MCP, provide a user_id parameter.');
        }

        $user = User::find($userId);

        if (! $user) {
            throw new InvalidArgumentException('User not found. Please provide a valid user ID.');
        }

        return $user;
    }
}
