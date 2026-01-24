<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateWorkoutTool;
use App\Mcp\Tools\DeleteWorkoutTool;
use App\Mcp\Tools\ListWorkoutsTool;
use App\Mcp\Tools\UpdateWorkoutTool;
use Laravel\Mcp\Server;

class WorkoutPlannerServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'FitApp Workout Planner';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'Manages workout plans for fitness tracking. Supports creating, updating, listing, and deleting workouts with detailed notes. Version 1 focuses on workout-level management without step builders - use notes to provide detailed workout instructions and guidance.';

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        CreateWorkoutTool::class,
        UpdateWorkoutTool::class,
        DeleteWorkoutTool::class,
        ListWorkoutsTool::class,
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
