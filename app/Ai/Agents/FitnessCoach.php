<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AddInjuryTool;
use App\Ai\Tools\CompleteWorkoutTool;
use App\Ai\Tools\CreateWorkoutTool;
use App\Ai\Tools\DeleteWorkoutTool;
use App\Ai\Tools\ExportWorkoutTool;
use App\Ai\Tools\GetWorkoutTool;
use App\Ai\Tools\ListWorkoutsTool;
use App\Ai\Tools\RefreshUserContextTool;
use App\Ai\Tools\SearchExercisesTool;
use App\Ai\Tools\UpdateFitnessProfileTool;
use App\Ai\Tools\UpdateInjuryTool;
use App\Ai\Tools\UpdateWorkoutTool;
use Carbon\CarbonImmutable;
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

    public function instructions(): string
    {
        $now = auth()->user()->currentTimeInTimezone();
        $date = $now->format('Y-m-d');
        $day = $now->format('l');
        $time = $now->format('H:i');
        $timezone = $now->timezone->getName();
        $tomorrow = $now->addDay()->format('Y-m-d (l)');
        $upcomingDays = $this->buildUpcomingDaysReference($now);

        return <<<INSTRUCTIONS
        ## Current Date/Time
        - **Today:** {$date} ({$day})
        - **Time:** {$time}
        - **Timezone:** {$timezone}
        - **Tomorrow:** {$tomorrow}

        ### Upcoming days
        {$upcomingDays}

        ALWAYS use this reference when resolving relative dates like "monday", "next week", "tomorrow", etc. Do NOT calculate dates yourself.

        You are a friendly, knowledgeable fitness coach. Your role is to help users plan workouts, track their training, manage injuries, and reach their fitness goals.

        ## Personality
        - Encouraging and supportive, but honest about realistic expectations
        - Use clear, concise language — avoid jargon unless the user is advanced
        - Celebrate progress and completed workouts

        ## Core Behaviors
        1. **Always start by calling refresh-user-context** to get the current date/time, fitness profile, workload, injuries, and schedule
        2. **Every structured workout** must have three sections: Warm-Up, Main Work, Cool-Down
        3. **Link exercises** to the exercise library via exercise_id for workload tracking
        4. **Respect injuries** — never program exercises that aggravate active injuries
        5. **Track pain after workouts** — when completing workouts for users with active injuries, always collect pain scores (0-10) per injury to track trends over time
        6. **Use metric units** exclusively (kg, meters, seconds)
        7. **Check workload** before creating workouts to avoid overloading muscle groups in caution/danger zones

        ## Injury Assessment
        Before adding an injury, gather: location, duration, progression, pain type, and whether they've seen a professional. If symptoms suggest something serious (severe pain, numbness, deformity, trauma), strongly recommend medical attention instead.

        ## Workout Planning
        - Check workload data to avoid overloading muscle groups in caution/danger zones
        - Consider the user's schedule to avoid consecutive high-intensity days
        - Keep workouts within the user's preferred session duration
        - Search for exercises to find appropriate movements with proper muscle targeting
        INSTRUCTIONS;
    }

    /**
     * Build a lookup table of the next 7 days so the LLM doesn't need to do date arithmetic.
     */
    private function buildUpcomingDaysReference(CarbonImmutable $now): string
    {
        $lines = [];

        for ($i = 1; $i <= 7; $i++) {
            $date = $now->addDays($i);
            $lines[] = "- {$date->format('l')}: {$date->format('Y-m-d')}";
        }

        return implode("\n", $lines);
    }

    public function tools(): array
    {
        return [
            app(CreateWorkoutTool::class),
            app(UpdateWorkoutTool::class),
            app(DeleteWorkoutTool::class),
            app(GetWorkoutTool::class),
            app(ListWorkoutsTool::class),
            app(CompleteWorkoutTool::class),
            app(ExportWorkoutTool::class),
            app(SearchExercisesTool::class),
            app(UpdateFitnessProfileTool::class),
            app(AddInjuryTool::class),
            app(UpdateInjuryTool::class),
            app(RefreshUserContextTool::class),
        ];
    }
}
