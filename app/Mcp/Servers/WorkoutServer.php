<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\UserProfileResource;
use App\Mcp\Resources\WorkoutScheduleResource;
use App\Mcp\Tools\AddInjuryTool;
use App\Mcp\Tools\CompleteWorkoutTool;
use App\Mcp\Tools\CreateWorkoutTool;
use App\Mcp\Tools\DeleteWorkoutTool;
use App\Mcp\Tools\ListWorkoutsTool;
use App\Mcp\Tools\PingTool;
use App\Mcp\Tools\RemoveInjuryTool;
use App\Mcp\Tools\UpdateFitnessProfileTool;
use App\Mcp\Tools\UpdateWorkoutTool;
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
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        The Workout Server enables AI-assisted workout planning and management. Users can create personalized workout plans for fitness goals like race preparation, injury recovery, and general fitness.

        ## User Identification

        All tools require a `user_id` parameter (integer). This is the ID of the user in the system.
        - For local MCP: Users specify their ID when calling tools
        - Dates and times are in the user's timezone (server handles UTC conversion automatically)

        ## Workout Sports

        Available sport types:
        - `running` - Running workouts (outdoor/treadmill)
        - `strength` - Strength training sessions
        - `cardio` - Cardiovascular exercise
        - `hiit` - High-Intensity Interval Training

        ## Rating Scales

        ### RPE (Rate of Perceived Exertion, 1-10)
        - 1-2: Very Easy
        - 3-4: Easy
        - 5-6: Moderate
        - 7-8: Hard
        - 9-10: Maximum Effort

        ### Feeling (Post-workout, 1-5)
        - 1: Terrible
        - 2: Poor
        - 3: Average
        - 4: Good
        - 5: Great

        ## Workout Lifecycle

        1. **Create** workout with sport, name, and schedule
        2. **Update** (optional) before completion - modify name, sport, schedule, or notes
        3. **Complete** with RPE and feeling ratings
        4. **Delete** only if not completed and not past (except today's workouts)

        ## Creating Workout Plans

        For multi-workout plans (e.g., marathon training, weekly routines):
        - Create multiple workouts with appropriate scheduled dates
        - Each workout is independent but can follow a progression
        - Use descriptive names to indicate plan structure
          - Example: "Week 1: Easy Run", "Week 1: Long Run", "Week 2: Tempo Run"

        ## Workout Notes

        Notes support **Markdown formatting** for rich text. You can use:
        - **Bold** text with `**text**`
        - *Italic* text with `*text*`
        - Lists with `- item` or `1. item`
        - [Links](url) with `[text](url)`
        - Headers with `# Header`

        ## Business Rules

        - Completed workouts cannot be edited or deleted
        - Past workouts cannot be deleted (except today's workouts)
        - Workouts can only be completed once
        - All dates/times are handled in the user's timezone

        ## Available Tools

        - **ping**: Test server connection
        - **create-workout**: Create a new workout
        - **update-workout**: Update an existing workout (if not completed)
        - **delete-workout**: Delete a workout (with business rule checks)
        - **complete-workout**: Mark workout as completed with ratings
        - **list-workouts**: Query workouts with filtering (upcoming/completed/overdue/all)
        - **update-fitness-profile**: Set or update user's fitness goals and availability
        - **add-injury**: Add an injury record to track limitations
        - **remove-injury**: Remove an injury record

        ## Available Resources

        - **user://profile/{userId}**: Read-only user profile information including fitness profile and injuries
        - **workout://schedule/{userId}**: Read-only workout schedule (upcoming & completed)
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        PingTool::class,
        CreateWorkoutTool::class,
        UpdateWorkoutTool::class,
        DeleteWorkoutTool::class,
        CompleteWorkoutTool::class,
        ListWorkoutsTool::class,
        UpdateFitnessProfileTool::class,
        AddInjuryTool::class,
        RemoveInjuryTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        UserProfileResource::class,
        WorkoutScheduleResource::class,
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
