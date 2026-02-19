<?php

namespace App\Mcp\Prompts;

use App\Actions\CalculateWorkload;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PlanTrainingProgramPrompt extends Prompt
{
    protected string $name = 'plan-training-program';

    protected string $description = 'Plan a multi-day or multi-week training program with progressive overload, deload weeks, and injury awareness.';

    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'program_type',
                description: 'Type of program (e.g. "5k training", "strength program", "general fitness", "marathon prep").',
                required: true,
            ),
            new Argument(
                name: 'duration_weeks',
                description: 'Program length in weeks (1-52).',
                required: true,
            ),
            new Argument(
                name: 'start_date',
                description: 'Program start date (YYYY-MM-DD). Defaults to next Monday.',
                required: false,
            ),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'program_type' => ['required', 'string', 'max:255'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'start_date' => ['nullable', 'date'],
        ]);

        $programType = $validated['program_type'];
        $durationWeeks = (int) $validated['duration_weeks'];
        $startDate = isset($validated['start_date'])
            ? CarbonImmutable::parse($validated['start_date'], $user->getTimezoneObject())
            : $user->currentTimeInTimezone()->next('Monday');

        $endDate = $startDate->addWeeks($durationWeeks)->subDay();
        $context = $this->buildContext($user, $programType, $durationWeeks, $startDate, $endDate);

        $acknowledgement = "I'll help you create a {$durationWeeks}-week {$programType} program starting {$startDate->format('M j, Y')}. Let me review your profile and current training state.";

        return Response::make([
            Response::text($acknowledgement)->asAssistant(),
            Response::text($context),
        ]);
    }

    private function buildContext(
        User $user,
        string $programType,
        int $durationWeeks,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): string {
        $sections = [];

        $sections[] = $this->buildProfileSection($user);
        $sections[] = $this->buildWorkloadSection($user);
        $sections[] = $this->buildInjurySection($user);
        $sections[] = $this->buildExistingScheduleSection($user, $startDate, $endDate);
        $sections[] = $this->buildProgramInstructions($programType, $durationWeeks, $startDate, $endDate);

        return implode("\n\n", array_filter($sections));
    }

    private function buildProfileSection(User $user): string
    {
        $profile = $user->fitnessProfile;

        if (! $profile) {
            return "## Fitness Profile\n\n*No fitness profile set up.* Ask the user about their goals, available days, and session duration before building the program.";
        }

        $content = "## Fitness Profile\n\n";
        $content .= "- **Goal:** {$profile->primary_goal->label()}\n";

        if ($profile->goal_details) {
            $content .= "- **Goal details:** {$profile->goal_details}\n";
        }

        $content .= "- **Available days/week:** {$profile->available_days_per_week}\n";
        $content .= "- **Minutes per session:** {$profile->minutes_per_session}\n";
        $content .= '- **Prefer Garmin exercises:** '.($profile->prefer_garmin_exercises ? 'Yes' : 'No');

        return $content;
    }

    private function buildWorkloadSection(User $user): string
    {
        $summary = $this->calculateWorkload->execute($user);
        $content = "## Current Workload (baseline)\n\n";

        if ($summary->sessionLoad === null && $summary->muscleGroupVolume->isEmpty()) {
            return $content.'*No workload data available.* Start the program at a conservative intensity and build up gradually.';
        }

        if ($summary->sessionLoad !== null) {
            $load = $summary->sessionLoad;
            $content .= "- Weekly total (sRPE): {$load->currentWeeklyTotal}\n";
            $content .= "- Sessions this week: {$load->currentSessionCount}\n";
            $content .= "- Monotony: {$load->monotony}\n\n";
            $content .= "Use this as the baseline for progressive overload. Week 1 should not exceed this load significantly.\n";
        }

        return $content;
    }

    private function buildInjurySection(User $user): string
    {
        $injuries = $user->injuries()->active()->get();

        if ($injuries->isEmpty()) {
            return '';
        }

        $content = "## Active Injuries\n\n";
        $content .= "These must be considered throughout the entire program:\n\n";
        foreach ($injuries as $injury) {
            $content .= "- **{$injury->body_part->value}** ({$injury->injury_type->value}) — since {$injury->started_at->toDateString()}";

            if ($injury->notes) {
                $content .= " — {$injury->notes}";
            }

            $content .= "\n";
        }

        return $content;
    }

    private function buildExistingScheduleSection(User $user, CarbonImmutable $startDate, CarbonImmutable $endDate): string
    {
        $existing = $user->workouts()
            ->where('scheduled_at', '>=', $startDate->startOfDay())
            ->where('scheduled_at', '<=', $endDate->endOfDay())
            ->orderBy('scheduled_at')
            ->get();

        if ($existing->isEmpty()) {
            return '';
        }

        $content = "## Existing Scheduled Workouts in Program Timeframe\n\n";
        $content .= "These workouts already exist and should be considered when building the program:\n\n";
        foreach ($existing as $workout) {
            $scheduledAt = $user->toUserTimezone(CarbonImmutable::instance($workout->scheduled_at));
            $status = $workout->isCompleted() ? '(completed)' : '(scheduled)';
            $content .= "- {$scheduledAt->format('D M j')}: {$workout->name} ({$workout->activity->label()}) {$status}\n";
        }

        return $content;
    }

    private function buildProgramInstructions(
        string $programType,
        int $durationWeeks,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): string {
        $content = "## Program Design Instructions\n\n";
        $content .= "Create a **{$durationWeeks}-week {$programType}** program from **{$startDate->format('Y-m-d')}** to **{$endDate->format('Y-m-d')}**.\n\n";

        $content .= "### Principles\n";
        $content .= "- **Progressive overload:** Gradually increase volume/intensity week over week (max ~10% weekly increase)\n";
        $content .= "- **Deload weeks:** Include a deload every 3-4 weeks (reduce volume by ~40-50%)\n";
        $content .= "- **Muscle balance:** Ensure opposing muscle groups are trained proportionally\n";
        $content .= "- **Recovery:** Schedule rest days based on the user's available days per week\n";
        $content .= "- **Injury awareness:** Avoid or modify exercises that stress injured areas\n\n";

        $content .= "### Steps\n";
        $content .= "1. Outline the weekly structure (which days, which workout types)\n";
        $content .= "2. Present the plan overview to the user for approval before creating workouts\n";
        $content .= "3. Use `search-exercises` to find appropriate exercises, linking them via `exercise_id`\n";
        $content .= "4. Use `create-workout` for each workout with warm-up, main work, and cool-down sections\n";
        $content .= '5. Use descriptive names indicating week and purpose (e.g., "Week 1: Easy Run", "Week 3: Tempo Run")';

        return $content;
    }
}
