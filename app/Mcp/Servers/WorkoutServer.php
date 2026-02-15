<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\CreateWorkoutPrompt;
use App\Mcp\Resources\MuscleGroupsResource;
use App\Mcp\Resources\UserFitnessProfileResource;
use App\Mcp\Resources\UserInjuriesResource;
use App\Mcp\Resources\UserProfileResource;
use App\Mcp\Resources\WorkloadResource;
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
        The Workout Server enables AI-assisted workout planning and management. Users can create personalized workout plans for fitness goals like race preparation, injury recovery, and general fitness.

        ## Authentication

        All requests require OAuth 2.1 authentication via Laravel Passport.

        - **OAuth Discovery:** `/.well-known/oauth-authorization-server`
        - **Endpoint:** `{app_url}/mcp/workout`
        - **User:** Automatically determined from the authenticated OAuth token

        ## User Identification

        - User is automatically authenticated via OAuth token
        - Dates and times are in the user's timezone (server handles UTC conversion automatically)

        ## Workout Lifecycle

        1. **Create** workout with activity, name, and schedule
        2. **Update** — modify name, activity, schedule, notes, or structure
        3. **Complete** with RPE and feeling ratings
        4. **Delete** when no longer needed

        ## Creating Workout Plans

        For multi-workout plans (e.g., marathon training, weekly routines):
        - Create multiple workouts with appropriate scheduled dates
        - Each workout is independent but can follow a progression
        - Use descriptive names to indicate plan structure (e.g., "Week 1: Easy Run", "Week 2: Tempo Run")

        ## Workout Structure

        Every structured workout MUST include three sections in this order:

        1. **Warm-Up** — Prepare the body for the main work. Include light cardio, dynamic stretches, or activation exercises relevant to the workout. Typically 5–10 minutes.
        2. **Main Work** — The core training block(s) with the primary exercises.
        3. **Cool-Down** — Aid recovery with static stretching, foam rolling, or light movement targeting the muscles worked. Typically 5–10 minutes.

        Adapt warm-up and cool-down content to the workout type:
        - **Strength:** Warm-up with light sets or mobility drills for the target muscles; cool-down with static stretches for worked muscle groups
        - **Running/Cardio:** Warm-up with easy pace or dynamic leg stretches; cool-down with walking and lower body stretches
        - **Yoga/Mobility:** Warm-up can be gentler; cool-down may include savasana or breathing exercises

        ## Workout Notes

        Notes support Markdown. Write detailed, actionable notes including: equipment needed, step-by-step phases (warm-up, main work, cool-down), sets/reps/intensity, rest periods, and modifications. Adapt detail level to workout type.

        ## Workload Tracking

        Use the `get-workload` tool to check muscle group load before creating workout plans:
        - **ACWR zones**: undertraining (<0.8), sweet_spot (0.8–1.3), caution (1.3–1.5), danger (>1.5)
        - Avoid programming heavy work for muscle groups in caution/danger zones
        - Prioritize undertrained muscle groups when balancing weekly plans
        - Cross-reference active injuries with muscle group load — if a muscle group near an injured body part is in caution/danger, flag this to the user
        - Link exercises to the exercise library (via `exercise_id`) to enable workload tracking

        ## Exercise Library

        Use the `search-exercises` tool to find exercises from the catalog:
        - Read the `exercise://muscle-groups` resource for a complete list of available muscle groups and their names
        - Search by name, muscle group, category, equipment, or difficulty level
        - Always link exercises to workouts via `exercise_id` to enable workload tracking
        - Primary muscles (load factor 1.0) receive full training volume
        - Secondary muscles (load factor 0.5) receive half the training volume
        - Cross-reference with workload zones before selecting exercises

        ## Garmin FIT Compatibility

        Some exercises in the catalog have Garmin FIT exercise mappings (`garmin_compatible: true` in search results). When these exercises are used in workouts, the FIT export includes Garmin exercise category and name IDs, enabling Garmin devices to display exercise animations and properly track exercises.

        - Check the user's `prefer_garmin_exercises` setting in their fitness profile (available via `user://fitness-profile` resource)
        - When enabled, use `garmin_compatible: true` filter in `search-exercises` to prefer mapped exercises
        - Unmapped exercises still export fine — they just won't show Garmin animations on the device

        ## Business Rules

        - Workouts can only be completed once
        - All dates/times are handled in the user's timezone

        ## Injury Assessment Protocol

        Before adding an injury using the `add-injury` tool, you MUST gather comprehensive information through a structured assessment. Follow these steps:

        ### Step 1: Location
        Ask: "Where are you experiencing the issue?"
        - Map the response to one of the supported body parts
        - If unclear, ask follow-up questions to pinpoint the exact location

        ### Step 2: Duration
        Ask: "How long have you been experiencing this issue?"
        - Use this to determine the `started_at` date
        - For acute injuries, get the specific date if possible

        ### Step 3: Progression
        Ask: "Are your symptoms getting better, worse, or staying the same?"
        - This helps determine injury type (acute vs chronic vs recurring)
        - Worsening symptoms may indicate a red flag

        ### Step 4: Pain Characteristics
        Ask: "What type of pain or discomfort do you feel?"
        - Sharp: Often indicates acute injury or nerve involvement
        - Dull: May suggest chronic condition or muscle fatigue
        - Aching: Common with overuse or inflammation
        - Burning: Could indicate nerve irritation or inflammation

        ### Step 5: Professional Consultation
        Ask: "Have you consulted a healthcare professional about this issue?"
        - Document their diagnosis or recommendations in the notes field
        - If they haven't and symptoms are concerning, recommend seeking professional advice

        ### Red Flags — Urgent Referral Required

        If ANY of the following are present, DO NOT proceed with adding the injury. Instead, strongly advise the user to seek immediate medical attention:

        - Severe pain that is unbearable or prevents sleep
        - Numbness, tingling, or loss of sensation
        - Visible deformity or significant swelling
        - Inability to bear weight or move the affected area
        - Pain following a traumatic incident (fall, collision, accident)
        - Symptoms accompanied by fever, chills, or feeling unwell
        - Rapidly worsening symptoms despite rest
        - Pain that radiates down arms or legs
        - Chest pain or difficulty breathing

        **Response template for red flags:**
        "Based on what you've described, I strongly recommend consulting a healthcare professional before continuing. [Specific symptom] can indicate a condition that requires proper medical evaluation. Please see a doctor or physiotherapist before we proceed with your training plan."

        ### After Assessment

        Once you have gathered all information and confirmed no red flags are present:
        1. Summarize the injury details back to the user for confirmation
        2. Use the `add-injury` tool with appropriate values
        3. Include relevant assessment notes in the `notes` field
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
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        CreateWorkoutPrompt::class,
    ];
}
