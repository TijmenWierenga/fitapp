<?php

namespace App\Ai\Agents;

use App\Actions\BuildCoachContext;
use App\Ai\Tools\AddInjuryTool;
use App\Ai\Tools\CompleteWorkoutTool;
use App\Ai\Tools\CreateWorkoutTool;
use App\Ai\Tools\DeleteWorkoutTool;
use App\Ai\Tools\ExportWorkoutTool;
use App\Ai\Tools\GetFitnessProfileTool;
use App\Ai\Tools\GetInjuriesTool;
use App\Ai\Tools\GetWorkloadTool;
use App\Ai\Tools\GetWorkoutScheduleTool;
use App\Ai\Tools\GetWorkoutTool;
use App\Ai\Tools\ListWorkoutsTool;
use App\Ai\Tools\SearchExercisesTool;
use App\Ai\Tools\UpdateFitnessProfileTool;
use App\Ai\Tools\UpdateInjuryTool;
use App\Ai\Tools\UpdateWorkoutTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
#[MaxSteps(10)]
#[Timeout(120)]
class FitnessCoach implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        private BuildCoachContext $buildCoachContext,
    ) {}

    public function instructions(): string
    {
        $baseInstructions = <<<'INSTRUCTIONS'
        You are a friendly, knowledgeable fitness coach. Your role is to help users plan workouts, track their training, manage injuries, and reach their fitness goals.

        ## Personality
        - Encouraging and supportive, but honest about realistic expectations
        - Use clear, concise language — avoid jargon unless the user is advanced
        - Celebrate progress and completed workouts
        - Proactively check workload before creating workouts to avoid overtraining

        ## Core Behaviors
        1. **Before creating workouts**, always check the user's workload and fitness profile
        2. **Every structured workout** must have three sections: Warm-Up, Main Work, Cool-Down
        3. **Link exercises** to the exercise library via exercise_id for workload tracking
        4. **Respect injuries** — never program exercises that aggravate active injuries
        5. **Use metric units** exclusively (kg, meters, seconds)

        ## Injury Assessment
        Before adding an injury, gather: location, duration, progression, pain type, and whether they've seen a professional. If symptoms suggest something serious (severe pain, numbness, deformity, trauma), strongly recommend medical attention instead.

        ## Workout Planning
        - Check workload data to avoid overloading muscle groups in caution/danger zones
        - Consider the user's schedule to avoid consecutive high-intensity days
        - Keep workouts within the user's preferred session duration
        - Search for exercises to find appropriate movements with proper muscle targeting
        INSTRUCTIONS;

        $user = auth()->user();

        if (! $user) {
            return $baseInstructions;
        }

        $context = $this->buildCoachContext->execute($user);

        return "{$baseInstructions}\n\n---\n\n# Current User Context\n\n{$context}";
    }

    public function tools(): array
    {
        return [
            app(CreateWorkoutTool::class),
            app(UpdateWorkoutTool::class),
            new DeleteWorkoutTool,
            new GetWorkoutTool,
            new ListWorkoutsTool,
            new CompleteWorkoutTool,
            app(ExportWorkoutTool::class),
            new SearchExercisesTool,
            app(GetWorkloadTool::class),
            new GetFitnessProfileTool,
            new UpdateFitnessProfileTool,
            new GetInjuriesTool,
            new AddInjuryTool,
            new UpdateInjuryTool,
            new GetWorkoutScheduleTool,
        ];
    }
}
