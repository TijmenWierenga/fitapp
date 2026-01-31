<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\UserProfileResource;
use App\Mcp\Resources\WorkoutScheduleResource;
use App\Mcp\Tools\AddInjuryTool;
use App\Mcp\Tools\CompleteWorkoutTool;
use App\Mcp\Tools\CreateWorkoutTool;
use App\Mcp\Tools\DeleteWorkoutTool;
use App\Mcp\Tools\GetTrainingAnalyticsTool;
use App\Mcp\Tools\GetWorkoutTool;
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

        ## Authentication

        All requests require authentication via Sanctum token.

        - **Endpoint:** `'.config('app.url').'/mcp/workout`
        - **Authentication:** Include a Bearer token in the Authorization header
        - **Header:** `Authorization: Bearer <api_token>`
        - **User:** Automatically determined from the authenticated token

        ## User Identification

        - User is automatically authenticated via Sanctum token
        - Dates and times are in the user's timezone (server handles UTC conversion automatically)

        ## Activity Types

        Activities are Garmin-compatible. Common types include:
        - **Running:** `run`, `trail_run`, `treadmill`, `track_run`, `ultra_run`
        - **Cycling:** `bike`, `bike_indoor`, `mountain_bike`, `road_bike`, `gravel_bike`
        - **Swimming:** `pool_swim`, `open_water`
        - **Gym:** `strength`, `cardio`, `hiit`, `elliptical`, `row_indoor`
        - **Walking:** `walk`, `hike`, `rucking`, `mountaineering`
        - **Flexibility:** `yoga`, `pilates`, `mobility`
        - **Racket:** `tennis`, `padel`, `badminton`, `squash`, `pickleball`
        - **Team:** `soccer`, `basketball`, `volleyball`, `rugby`
        - **Other:** `golf`, `meditation`, `triathlon`, `other`, and many more

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

        1. **Create** workout with activity, name, and schedule
        2. **Update** (optional) before completion - modify name, activity, schedule, or notes
        3. **Complete** with RPE and feeling ratings
        4. **Delete** only if not completed and not past (except today's workouts)

        ## Creating Workout Plans

        For multi-workout plans (e.g., marathon training, weekly routines):
        - Create multiple workouts with appropriate scheduled dates
        - Each workout is independent but can follow a progression
        - Use descriptive names to indicate plan structure
          - Example: "Week 1: Easy Run", "Week 1: Long Run", "Week 2: Tempo Run"

        ## Workout Notes

        Notes support **Markdown formatting** for rich text. Always write detailed, actionable notes that help the user follow the workout independently. Include:

        - **Equipment needed** — list any gear (e.g., dumbbells, resistance bands, barbell, treadmill, yoga mat)
        - **Step-by-step workout plan** — break the session into phases (warm-up, main work, cool-down)
        - **Sets, reps & intensity** — specify numbers, weight suggestions, pace, or RPE where applicable
        - **Rest periods** — note rest between sets or intervals
        - **Modifications or alternatives** — suggest easier/harder variations when helpful

        ### Example note

        ```markdown
        ## Equipment
        - Pair of dumbbells (moderate weight)
        - Yoga mat

        ## Warm-Up (5 min)
        - 2 min light jog in place
        - Arm circles — 30 sec each direction
        - Leg swings — 10 each side

        ## Main Workout
        1. **Goblet Squats** — 3 × 12 reps (rest 60 sec between sets)
        2. **Dumbbell Lunges** — 3 × 10 each leg (rest 60 sec)
        3. **Dumbbell Shoulder Press** — 3 × 10 reps (rest 90 sec)
        4. **Plank Hold** — 3 × 45 sec (rest 30 sec)

        ## Cool-Down (5 min)
        - Standing quad stretch — 30 sec each side
        - Seated hamstring stretch — 30 sec each side
        - Deep breathing — 1 min
        ```

        Adapt the level of detail to the workout type: a simple "Easy Run" may only need pace and distance guidance, while a strength session benefits from full set/rep breakdowns.

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
        - **get-workout**: Fetch a single workout by ID with full details
        - **get-training-analytics**: Aggregated training stats (completion rate, RPE, streaks, etc.)
        - **update-fitness-profile**: Set or update user's fitness goals and availability
        - **add-injury**: Add an injury record to track limitations
        - **remove-injury**: Remove an injury record

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

        ### Red Flags - Urgent Referral Required

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

        ## Available Resources

        - **user://profile**: Read-only user profile information including fitness profile and injuries
        - **workout://schedule**: Read-only workout schedule (upcoming & completed). Supports optional `upcoming_limit` (default 20, max 50) and `completed_limit` (default 10, max 50) parameters.
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
        GetTrainingAnalyticsTool::class,
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
