<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\AssessInjuryPrompt;
use App\Mcp\Prompts\PlanTrainingProgramPrompt;
use App\Mcp\Prompts\PlanWorkoutPrompt;
use App\Mcp\Prompts\ReviewProgressPrompt;
use App\Mcp\Resources\MuscleGroupsResource;
use App\Mcp\Resources\UserFitnessProfileResource;
use App\Mcp\Resources\UserInjuriesResource;
use App\Mcp\Resources\UserProfileResource;
use App\Mcp\Resources\WorkloadResource;
use App\Mcp\Resources\WorkloadZonesResource;
use App\Mcp\Resources\WorkoutGuidelinesResource;
use App\Mcp\Resources\WorkoutScheduleResource;
use App\Mcp\Tools\AddInjuryReportTool;
use App\Mcp\Tools\AddInjuryTool;
use App\Mcp\Tools\CompleteWorkoutTool;
use App\Mcp\Tools\CreateExerciseTool;
use App\Mcp\Tools\CreateWorkoutTool;
use App\Mcp\Tools\DeleteInjuryReportTool;
use App\Mcp\Tools\DeleteWorkoutTool;
use App\Mcp\Tools\ExportWorkoutTool;
use App\Mcp\Tools\GetFitnessProfileTool;
use App\Mcp\Tools\GetInjuriesTool;
use App\Mcp\Tools\GetUserProfileTool;
use App\Mcp\Tools\GetWorkloadTool;
use App\Mcp\Tools\GetWorkoutScheduleTool;
use App\Mcp\Tools\GetWorkoutTool;
use App\Mcp\Tools\ListInjuryReportsTool;
use App\Mcp\Tools\ListWorkoutsTool;
use App\Mcp\Tools\PingTool;
use App\Mcp\Tools\RemoveInjuryTool;
use App\Mcp\Tools\SearchExercisesTool;
use App\Mcp\Tools\UpdateFitnessProfileTool;
use App\Mcp\Tools\UpdateInjuryReportTool;
use App\Mcp\Tools\UpdateInjuryTool;
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
     * The default number of items per page when listing tools, resources, or prompts.
     */
    public int $defaultPaginationLength = 50;

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        The Workout Server enables AI-assisted workout planning and management for fitness goals like race preparation, injury recovery, and general fitness.

        ## Workout Lifecycle

        1. **Create** workout with activity, name, and schedule
        2. **Update** — modify name, activity, schedule, notes, or structure
        3. **Complete** with RPE, feeling ratings, and optional pain scores for active injuries
        4. **Delete** when no longer needed

        ## Creating Workout Plans

        For multi-workout plans (e.g., marathon training, weekly routines), create multiple workouts with appropriate scheduled dates. Each workout is independent but can follow a progression. Use descriptive names (e.g., "Week 1: Easy Run").

        ## Workout Notes

        Notes support Markdown. Include equipment needed, phases, sets/reps/intensity, rest periods, and modifications.

        ## Key Resources & Prompts

        - `workout://guidelines` — Workout structure requirements, activity-specific conventions, Garmin FIT compatibility, and pain score scale. **Read when creating or completing workouts.**
        - `workout://workload-zones` — ACWR zone definitions and decision rules. **Read before creating workout plans.**
        - `exercise://muscle-groups` — Available muscle groups for exercise search.
        - `assess-injury` prompt — Follow this protocol before adding injuries via the `add-injury` tool.

        ## Exercise Library

        Use `search-exercises` to find exercises. Always link via `exercise_id` for workload tracking. Primary muscles receive full volume (1.0); secondary muscles receive half (0.5).

        ## Business Rules

        - Workouts can only be completed once
        - All dates/times are handled in the user's timezone
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
        GetWorkoutTool::class,
        GetUserProfileTool::class,
        GetFitnessProfileTool::class,
        GetInjuriesTool::class,
        GetWorkloadTool::class,
        GetWorkoutScheduleTool::class,
        UpdateFitnessProfileTool::class,
        AddInjuryTool::class,
        UpdateInjuryTool::class,
        RemoveInjuryTool::class,
        AddInjuryReportTool::class,
        ListInjuryReportsTool::class,
        UpdateInjuryReportTool::class,
        DeleteInjuryReportTool::class,
        SearchExercisesTool::class,
        CreateExerciseTool::class,
        ExportWorkoutTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        UserProfileResource::class,
        UserFitnessProfileResource::class,
        UserInjuriesResource::class,
        WorkoutScheduleResource::class,
        WorkloadResource::class,
        MuscleGroupsResource::class,
        WorkoutGuidelinesResource::class,
        WorkloadZonesResource::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        PlanWorkoutPrompt::class,
        PlanTrainingProgramPrompt::class,
        AssessInjuryPrompt::class,
        ReviewProgressPrompt::class,
    ];
}
