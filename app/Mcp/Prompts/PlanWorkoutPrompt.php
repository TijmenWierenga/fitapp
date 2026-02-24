<?php

namespace App\Mcp\Prompts;

use App\Actions\BuildCoachContext;
use App\Enums\Workout\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PlanWorkoutPrompt extends Prompt
{
    protected string $name = 'plan-workout';

    protected string $description = 'Plan a workout with full user context including fitness profile, current workload, active injuries, and schedule.';

    public function __construct(
        private BuildCoachContext $buildCoachContext,
    ) {}

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'activity',
                description: 'Workout activity type (e.g. strength, run, yoga). When omitted, the assistant recommends what to do.',
                required: false,
            ),
            new Argument(
                name: 'date',
                description: 'Target date for the workout (YYYY-MM-DD). Defaults to today.',
                required: false,
            ),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        /** @var User $user */
        $user = $request->user();

        $activityValue = $request->get('activity');
        $activity = $activityValue ? Activity::tryFrom($activityValue) : null;
        $date = $request->get('date')
            ? CarbonImmutable::parse($request->get('date'), $user->getTimezoneObject())
            : $user->currentTimeInTimezone();

        $context = $this->buildCoachContext->execute($user, $activity, $date);

        $activityLabel = $activity ? $activity->label() : 'a workout';
        $acknowledgement = "I'll help you plan {$activityLabel} for {$date->format('l, M j, Y')}. Let me review your profile and current training load.";

        return Response::make([
            Response::text($acknowledgement)->asAssistant(),
            Response::text($context),
        ]);
    }
}
