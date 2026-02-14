<?php

namespace App\Mcp\Prompts;

use App\Actions\CalculateWorkload;
use App\DataTransferObjects\Workload\MuscleGroupWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use App\Enums\BodyPart;
use App\Enums\FitnessGoal;
use App\Enums\WorkloadZone;
use App\Enums\Workout\Activity;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class CreateWorkoutPrompt extends Prompt
{
    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    protected string $description = <<<'MARKDOWN'
        Interactive guide for creating a new workout with smart defaults based on your fitness profile, schedule, and any active injuries.

        All arguments are optional. The prompt will guide you through any missing information step-by-step.
    MARKDOWN;

    public function handle(Request $request): Response|array
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'activity' => ['nullable', Rule::enum(Activity::class)],
            'scheduled_at' => 'nullable|string',
            'duration' => 'nullable|integer|min:15|max:180',
            'include_notes' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $user->load(['fitnessProfile', 'injuries']);

        $context = $this->gatherContext($user);

        $responses = [];

        // Step 1: Initial greeting and context summary
        $responses[] = Response::text($this->buildGreeting($user, $context))->asAssistant();

        // Step 2: Activity selection
        if (isset($validated['activity'])) {
            $activity = Activity::from($validated['activity']);
            $responses[] = Response::text("I see you want to create a {$activity->label()} workout. Great choice!");
        } else {
            $responses[] = Response::text($this->buildActivityPrompt($context));
        }

        // Step 3: Workout name suggestion
        if (! isset($validated['name'])) {
            $responses[] = Response::text($this->buildNamePrompt($validated['activity'] ?? null));
        }

        // Step 4: Scheduling
        if (! isset($validated['scheduled_at'])) {
            $responses[] = Response::text($this->buildSchedulePrompt($user, $context['upcoming_workouts']));
        }

        // Step 5: Duration confirmation
        if (! isset($validated['duration'])) {
            $defaultDuration = $context['fitness_profile']?->minutes_per_session ?? 45;
            $responses[] = Response::text("How long should this workout be? (Default: {$defaultDuration} minutes based on your fitness profile)");
        }

        // Step 6: Exercise selection guidance
        $responses[] = Response::text($this->buildExerciseSelectionGuidance())->asAssistant();

        // Step 7: Workload-aware constraints
        $workloadGuidance = $this->buildWorkloadGuidance($context['workload']);
        if ($workloadGuidance) {
            $responses[] = Response::text($workloadGuidance)->asAssistant();
        }

        // Step 8: Notes generation guidance
        $includeNotes = $validated['include_notes'] ?? true;
        if ($includeNotes) {
            $responses[] = Response::text($this->buildNotesGuidance($context))->asAssistant();
        }

        // Step 9: Injury constraints reminder (if applicable)
        if ($context['active_injuries']->isNotEmpty()) {
            $responses[] = Response::text($this->buildInjuryConstraints($context['active_injuries']))->asAssistant();
        }

        // Step 10: Final instructions
        $responses[] = Response::text($this->buildFinalInstructions())->asAssistant();

        return $responses;
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'name',
                description: 'The workout name/title (e.g., "Morning Run", "Leg Day")',
                required: false,
            ),
            new Argument(
                name: 'activity',
                description: 'The activity type (e.g., run, strength, yoga)',
                required: false,
            ),
            new Argument(
                name: 'scheduled_at',
                description: 'When to schedule the workout (e.g., "tomorrow at 7am", "next Monday 6pm")',
                required: false,
            ),
            new Argument(
                name: 'duration',
                description: 'Expected workout duration in minutes',
                required: false,
            ),
            new Argument(
                name: 'include_notes',
                description: 'Whether to generate detailed workout notes (true/false). Defaults to true.',
                required: false,
            ),
        ];
    }

    /**
     * @return array{fitness_profile: \App\Models\FitnessProfile|null, active_injuries: Collection<int, Injury>, upcoming_workouts: Collection<int, Workout>, workload: WorkloadSummary}
     */
    protected function gatherContext(User $user): array
    {
        return [
            'fitness_profile' => $user->fitnessProfile,
            'active_injuries' => $user->injuries->filter(fn (Injury $injury): bool => $injury->is_active),
            'upcoming_workouts' => $user->workouts()->upcoming()->limit(10)->get(),
            'workload' => $this->calculateWorkload->execute($user),
        ];
    }

    protected function buildGreeting(User $user, array $context): string
    {
        $greeting = "# Let's Create Your Workout\n\n";
        $greeting .= "Hi {$user->name}! I'll help you create a new workout tailored to your goals and current situation.\n\n";

        // Fitness profile summary
        if ($fitnessProfile = $context['fitness_profile']) {
            $greeting .= "## Your Fitness Profile\n\n";
            $greeting .= "- **Goal:** {$fitnessProfile->primary_goal->label()}";
            if ($fitnessProfile->goal_details) {
                $greeting .= " ({$fitnessProfile->goal_details})";
            }
            $greeting .= "\n";
            $greeting .= "- **Training Days/Week:** {$fitnessProfile->available_days_per_week}\n";
            $greeting .= "- **Session Duration:** {$fitnessProfile->minutes_per_session} minutes\n\n";
        } else {
            $greeting .= "**Note:** You haven't set up a fitness profile yet. I'll use general defaults, but consider using the `update-fitness-profile` tool later for better recommendations.\n\n";
        }

        // Active injuries
        $activeInjuries = $context['active_injuries'];
        if ($activeInjuries->isNotEmpty()) {
            $greeting .= "## Active Injuries\n\n";
            $greeting .= "I'll keep these in mind when suggesting activities:\n\n";
            foreach ($activeInjuries as $injury) {
                $greeting .= "- **{$injury->body_part->label()}** ({$injury->injury_type->label()})";
                if ($injury->notes) {
                    $greeting .= " - {$injury->notes}";
                }
                $greeting .= "\n";
            }
            $greeting .= "\n";
        }

        // Workload summary
        $workload = $context['workload'];
        if ($workload->muscleGroups->isNotEmpty()) {
            $greeting .= "## Current Workload\n\n";

            $cautionGroups = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Caution);
            $dangerGroups = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Danger);
            $undertrainingGroups = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Undertraining);

            if ($dangerGroups->isNotEmpty()) {
                $names = $dangerGroups->map(fn (MuscleGroupWorkload $w): string => "{$w->muscleGroupLabel} (ACWR {$w->acwr})")->implode(', ');
                $greeting .= "- **DANGER:** {$names} — strongly recommend reducing load\n";
            }

            if ($cautionGroups->isNotEmpty()) {
                $names = $cautionGroups->map(fn (MuscleGroupWorkload $w): string => "{$w->muscleGroupLabel} (ACWR {$w->acwr})")->implode(', ');
                $greeting .= "- **CAUTION:** {$names} — consider reducing load\n";
            }

            if ($undertrainingGroups->isNotEmpty()) {
                $names = $undertrainingGroups->map(fn (MuscleGroupWorkload $w): string => $w->muscleGroupLabel)->implode(', ');
                $greeting .= "- **Undertrained:** {$names} — could use more stimulus\n";
            }

            $sweetSpotCount = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::SweetSpot)->count();
            if ($sweetSpotCount > 0) {
                $greeting .= "- **Sweet Spot:** {$sweetSpotCount} muscle group(s) in optimal training zone\n";
            }

            // Cross-reference injuries with workload
            if ($activeInjuries->isNotEmpty() && ($cautionGroups->isNotEmpty() || $dangerGroups->isNotEmpty())) {
                $warningGroups = $cautionGroups->merge($dangerGroups);
                $injuredBodyParts = $activeInjuries->pluck('body_part')->unique();

                foreach ($warningGroups as $workloadItem) {
                    if ($injuredBodyParts->contains(fn (BodyPart $bp): bool => $bp->value === $workloadItem->bodyPart)) {
                        $greeting .= "- **WARNING:** {$workloadItem->muscleGroupLabel} is in {$workloadItem->zone->value} zone AND near an active injury\n";
                    }
                }
            }

            $greeting .= "\n";
        }

        // Upcoming workouts preview
        $upcomingWorkouts = $context['upcoming_workouts'];
        if ($upcomingWorkouts->isNotEmpty()) {
            $greeting .= "## Your Upcoming Schedule\n\n";
            $count = min($upcomingWorkouts->count(), 5);
            foreach ($upcomingWorkouts->take($count) as $workout) {
                $scheduledAt = $user->toUserTimezone($workout->scheduled_at)->format('M d, H:i');
                $greeting .= "- {$scheduledAt}: **{$workout->name}** ({$workout->activity->label()})\n";
            }
            $greeting .= "\n";
        }

        return $greeting;
    }

    protected function buildActivityPrompt(array $context): string
    {
        $prompt = "## Choose Your Activity\n\n";

        $fitnessProfile = $context['fitness_profile'];
        $activeInjuries = $context['active_injuries'];

        $goal = $fitnessProfile?->primary_goal ?? FitnessGoal::GeneralFitness;
        $suggestedActivities = $this->suggestActivities($goal, $activeInjuries);

        $prompt .= "Based on your goal ({$goal->label()}), here are some recommended activities:\n\n";

        foreach ($suggestedActivities as $activity) {
            $prompt .= "- **{$activity->label()}** ({$activity->value})";

            // Add injury-related notes
            if ($activeInjuries->isNotEmpty()) {
                $modifications = $this->getActivityModifications($activity, $activeInjuries);
                if ($modifications) {
                    $prompt .= "\n  ⚠️  {$modifications}";
                }
            }

            $prompt .= "\n";
        }

        $prompt .= "\nWhat type of workout would you like to create? (Choose from above or specify any activity type)";

        return $prompt;
    }

    /**
     * @param  Collection<int, Injury>  $activeInjuries
     * @return array<Activity>
     */
    protected function suggestActivities(FitnessGoal $goal, Collection $activeInjuries): array
    {
        $baseActivities = match ($goal) {
            FitnessGoal::WeightLoss => [Activity::HIIT, Activity::Run, Activity::Bike, Activity::Cardio, Activity::PoolSwim],
            FitnessGoal::MuscleGain => [Activity::Strength, Activity::HIIT, Activity::JumpRope],
            FitnessGoal::Endurance => [Activity::Run, Activity::Bike, Activity::PoolSwim, Activity::Cardio, Activity::RowIndoor],
            FitnessGoal::GeneralFitness => [Activity::Strength, Activity::Cardio, Activity::Yoga, Activity::Run, Activity::Bike],
        };

        return $this->filterActivitiesByInjuries($baseActivities, $activeInjuries);
    }

    /**
     * @param  array<Activity>  $activities
     * @param  Collection<int, Injury>  $activeInjuries
     * @return array<Activity>
     */
    protected function filterActivitiesByInjuries(array $activities, Collection $activeInjuries): array
    {
        if ($activeInjuries->isEmpty()) {
            return $activities;
        }

        $bodyParts = $activeInjuries->pluck('body_part')->unique();

        $filtered = array_filter($activities, function (Activity $activity) use ($bodyParts) {
            foreach ($bodyParts as $bodyPart) {
                if ($this->shouldAvoidActivity($activity, $bodyPart)) {
                    return false;
                }
            }

            return true;
        });

        // If all activities filtered out, suggest low-impact alternatives
        if (empty($filtered)) {
            return [Activity::PoolSwim, Activity::Yoga, Activity::Mobility, Activity::Meditation];
        }

        return array_values($filtered);
    }

    protected function shouldAvoidActivity(Activity $activity, BodyPart $bodyPart): bool
    {
        return match ($bodyPart) {
            BodyPart::Ankle, BodyPart::Foot => in_array($activity, [Activity::Run, Activity::Hike, Activity::JumpRope, Activity::HIIT]),
            BodyPart::Knee => in_array($activity, [Activity::Run, Activity::HIIT, Activity::JumpRope, Activity::Hike]),
            BodyPart::Shoulder, BodyPart::Elbow, BodyPart::Wrist => in_array($activity, [Activity::Strength, Activity::PoolSwim, Activity::RowIndoor]),
            BodyPart::LowerBack, BodyPart::Core => in_array($activity, [Activity::HIIT, Activity::Strength]),
            BodyPart::Hip, BodyPart::Hamstring, BodyPart::Quadriceps => in_array($activity, [Activity::Run, Activity::HIIT]),
            default => false,
        };
    }

    /**
     * @param  Collection<int, Injury>  $activeInjuries
     */
    protected function getActivityModifications(Activity $activity, Collection $activeInjuries): ?string
    {
        $modifications = [];

        foreach ($activeInjuries as $injury) {
            $bodyPart = $injury->body_part;

            $modification = match (true) {
                $activity === Activity::Run && in_array($bodyPart, [BodyPart::Ankle, BodyPart::Foot, BodyPart::Knee]) => 'Consider low-impact alternatives like swimming or cycling',
                $activity === Activity::Strength && in_array($bodyPart, [BodyPart::Shoulder, BodyPart::Elbow, BodyPart::Wrist]) => 'Avoid upper body exercises; focus on lower body',
                $activity === Activity::Strength && in_array($bodyPart, [BodyPart::LowerBack, BodyPart::Hip, BodyPart::Knee]) => 'Avoid lower body exercises; focus on upper body',
                $activity === Activity::PoolSwim && in_array($bodyPart, [BodyPart::Shoulder, BodyPart::Elbow]) => 'Modify stroke to reduce shoulder strain (e.g., use pull buoy, focus on legs)',
                $activity === Activity::Yoga && $bodyPart === BodyPart::LowerBack => 'Avoid deep forward folds and twists',
                default => null,
            };

            if ($modification) {
                $modifications[] = $modification;
            }
        }

        return empty($modifications) ? null : implode('; ', array_unique($modifications));
    }

    protected function buildNamePrompt(?string $activityValue): string
    {
        $prompt = "## Name Your Workout\n\n";

        if ($activityValue) {
            $activity = Activity::from($activityValue);
            $suggestions = $this->suggestWorkoutNames($activity);

            $prompt .= "Here are some name suggestions for your {$activity->label()} workout:\n\n";
            foreach ($suggestions as $suggestion) {
                $prompt .= "- {$suggestion}\n";
            }
            $prompt .= "\nWhat would you like to call this workout?";
        } else {
            $prompt .= 'What would you like to call this workout? (e.g., "Morning Run", "Leg Day", "Recovery Yoga")';
        }

        return $prompt;
    }

    /**
     * @return array<string>
     */
    protected function suggestWorkoutNames(Activity $activity): array
    {
        return match ($activity) {
            Activity::Run, Activity::TrailRun, Activity::Treadmill => ['Easy Run', 'Tempo Run', 'Long Run', 'Interval Run'],
            Activity::Strength => ['Upper Body', 'Lower Body', 'Full Body', 'Push Day', 'Pull Day', 'Leg Day'],
            Activity::HIIT => ['HIIT Session', 'Tabata Workout', 'Circuit Training', 'Interval Training'],
            Activity::Yoga => ['Morning Yoga', 'Recovery Yoga', 'Power Yoga', 'Gentle Stretch'],
            Activity::Bike, Activity::BikeIndoor => ['Easy Ride', 'Hill Intervals', 'Endurance Ride', 'Recovery Spin'],
            Activity::PoolSwim => ['Technique Work', 'Endurance Swim', 'Sprint Intervals', 'Recovery Swim'],
            Activity::Cardio => ['Cardio Session', 'Fat Burn', 'Steady State', 'Mixed Cardio'],
            default => ["{$activity->label()} Workout", "Easy {$activity->label()}", "Hard {$activity->label()}"],
        };
    }

    /**
     * @param  Collection<int, Workout>  $upcomingWorkouts
     */
    protected function buildSchedulePrompt(User $user, Collection $upcomingWorkouts): string
    {
        $prompt = "## Schedule Your Workout\n\n";

        $suggestions = $this->suggestScheduleTimes($user, $upcomingWorkouts);

        if (! empty($suggestions)) {
            $prompt .= "Here are some available time slots:\n\n";
            foreach ($suggestions as $suggestion) {
                $formatted = $suggestion->format('l, M d \a\t g:i A');
                $prompt .= "- {$formatted}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= 'When would you like to schedule this workout? (e.g., "tomorrow at 7am", "next Monday 6pm", "2026-02-05 18:00")';

        return $prompt;
    }

    /**
     * @param  Collection<int, Workout>  $upcomingWorkouts
     * @return array<CarbonImmutable>
     */
    protected function suggestScheduleTimes(User $user, Collection $upcomingWorkouts): array
    {
        $now = $user->currentTimeInTimezone();
        $suggestions = [];

        // Look ahead 7 days, find 4 non-conflicting slots
        for ($day = 1; $day <= 7 && count($suggestions) < 4; $day++) {
            $candidate = $now->addDays($day);

            // Try morning slot (7 AM)
            $morning = $candidate->setTime(7, 0);
            if (! $this->hasConflict($morning, $upcomingWorkouts, $user) && count($suggestions) < 4) {
                $suggestions[] = $morning;
            }

            // Try evening slot (6 PM)
            $evening = $candidate->setTime(18, 0);
            if (! $this->hasConflict($evening, $upcomingWorkouts, $user) && count($suggestions) < 4) {
                $suggestions[] = $evening;
            }
        }

        return $suggestions;
    }

    /**
     * @param  Collection<int, Workout>  $upcomingWorkouts
     */
    protected function hasConflict(CarbonImmutable $candidate, Collection $upcomingWorkouts, User $user): bool
    {
        $candidateUtc = $candidate->setTimezone('UTC');

        foreach ($upcomingWorkouts as $workout) {
            $workoutTime = $workout->scheduled_at;
            $diffInHours = abs($candidateUtc->diffInHours($workoutTime));

            // Consider it a conflict if within 2 hours
            if ($diffInHours < 2) {
                return true;
            }
        }

        return false;
    }

    protected function buildNotesGuidance(array $context): string
    {
        $guidance = "## Workout Notes\n\n";
        $guidance .= "I'll generate detailed workout notes in Markdown format. These will include:\n\n";
        $guidance .= "- **Equipment needed** for the workout\n";
        $guidance .= "- **Warm-up phase** to prepare your body\n";
        $guidance .= "- **Main workout** with sets, reps, and intensity guidance\n";
        $guidance .= "- **Cool-down** to aid recovery\n";

        $activeInjuries = $context['active_injuries'];
        if ($activeInjuries->isNotEmpty()) {
            $guidance .= "\nI'll also include **modifications** to accommodate your active injuries:\n";
            foreach ($activeInjuries as $injury) {
                $guidance .= "- {$injury->body_part->label()} ({$injury->injury_type->label()})\n";
            }
        }

        $guidance .= "\n";

        return $guidance;
    }

    /**
     * @param  Collection<int, Injury>  $activeInjuries
     */
    protected function buildInjuryConstraints(Collection $activeInjuries): string
    {
        $constraints = "## ⚠️ Important: Active Injury Constraints\n\n";
        $constraints .= "**CRITICAL:** The following active injuries MUST be considered when creating this workout:\n\n";

        foreach ($activeInjuries as $injury) {
            $constraints .= "- **{$injury->body_part->label()}** ({$injury->injury_type->label()})";
            if ($injury->notes) {
                $constraints .= "\n  - {$injury->notes}";
            }
            $constraints .= "\n";
        }

        $constraints .= "\n**You MUST:**\n";
        $constraints .= "1. Avoid exercises that stress the injured body parts listed above\n";
        $constraints .= "2. Include specific modifications or alternatives in the workout notes\n";
        $constraints .= "3. Add injury-specific warnings in the workout notes where applicable\n";
        $constraints .= "4. Consider lower intensity variations if the chosen activity involves affected areas\n";

        return $constraints;
    }

    protected function buildExerciseSelectionGuidance(): string
    {
        $guidance = "## Exercise Selection\n\n";
        $guidance .= "When building this workout, use the `search-exercises` tool to find exercises from the library:\n\n";
        $guidance .= "- **Always** link exercises via `exercise_id` to enable workload tracking\n";
        $guidance .= "- Search by name, muscle group, category, equipment, or difficulty level\n";
        $guidance .= "- Prefer compound exercises for efficiency when time is limited\n";
        $guidance .= "- Include exercise alternatives for injured body parts\n";
        $guidance .= "- Primary muscles (load factor 1.0) receive full training stimulus\n";
        $guidance .= "- Secondary muscles (load factor 0.5) receive half the training stimulus\n";

        return $guidance;
    }

    protected function buildWorkloadGuidance(WorkloadSummary $workload): ?string
    {
        $cautionGroups = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Caution);
        $dangerGroups = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Danger);
        $undertrainingGroups = $workload->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Undertraining);

        if ($cautionGroups->isEmpty() && $dangerGroups->isEmpty() && $undertrainingGroups->isEmpty()) {
            return null;
        }

        $guidance = "## Workload-Aware Constraints\n\n";

        if ($dangerGroups->isNotEmpty()) {
            $guidance .= "**AVOID** exercises targeting these muscle groups (danger zone):\n";
            foreach ($dangerGroups as $w) {
                $guidance .= "- {$w->muscleGroupLabel} — ACWR {$w->acwr}, strongly recommend no additional load\n";
            }
            $guidance .= "\n";
        }

        if ($cautionGroups->isNotEmpty()) {
            $guidance .= "**REDUCE** volume for these muscle groups (caution zone):\n";
            foreach ($cautionGroups as $w) {
                $guidance .= "- {$w->muscleGroupLabel} — ACWR {$w->acwr}, reduce sets/reps or use lighter intensity\n";
            }
            $guidance .= "\n";
        }

        if ($undertrainingGroups->isNotEmpty()) {
            $guidance .= "**PRIORITIZE** these undertrained muscle groups when possible:\n";
            foreach ($undertrainingGroups as $w) {
                $guidance .= "- {$w->muscleGroupLabel} — ACWR {$w->acwr}, could benefit from more stimulus\n";
            }
            $guidance .= "\n";
        }

        return $guidance;
    }

    protected function buildFinalInstructions(): string
    {
        return <<<'TEXT'
## Ready to Create

Once you provide all the details, I'll use the `create-workout` tool to create your workout with:

1. **Name** - Descriptive workout title
2. **Activity** - Type of workout
3. **Scheduled Time** - When you'll do it (in your timezone)
4. **Structure** - Sections, blocks, and exercises with `exercise_id` from the library
5. **Notes** - Detailed workout plan with proper structure and injury modifications

Use the `search-exercises` tool to find appropriate exercises and link them via `exercise_id` for workload tracking.

Let's get started!
TEXT;
    }
}
