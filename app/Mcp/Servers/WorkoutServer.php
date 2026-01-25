<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\PingTool;
use Laravel\Mcp\Server;

class WorkoutServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Workout Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        The Workout Server provides tools to manage workout programs, exercises, and training sessions.

        ## Available Tools

        - **ping**: Test the connection to the server and verify it's responding correctly.

        ## Future Features

        Additional tools for managing workouts, exercises, and training data will be added soon.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        PingTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
