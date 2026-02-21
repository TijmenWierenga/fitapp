<?php

namespace App\Ai\Tools;

use App\Actions\CreateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Mcp\Tools\WorkoutResponseFormatter;
use App\Mcp\Tools\WorkoutSchemaBuilder;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CreateWorkoutTool implements Tool
{
    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
        private CreateStructuredWorkout $createStructuredWorkout,
    ) {}

    public function description(): string
    {
        return <<<'TEXT'
        Create a new workout with a scheduled date and time. Optionally include structured sections with blocks and exercises.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities.

        Dates/times should be in the user's local timezone. You can optionally provide a `sections` array to create a fully structured workout with warm-up, main work, and cool-down sections.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name/title of the workout'),
            'activity' => $schema->string()->enum(Activity::class)->description('The activity type.'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional Markdown notes for the workout.')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Optional structured workout sections with blocks and exercises.')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $scheduledAt = CarbonImmutable::parse($request['scheduled_at'], $user->getTimezoneObject())->utc();

        if (! empty($request['sections'])) {
            $sections = collect($request['sections'])
                ->map(fn (array $section): SectionData => SectionData::fromArray($section));

            $workout = $this->createStructuredWorkout->execute(
                user: $user,
                name: $request['name'],
                activity: Activity::from($request['activity']),
                scheduledAt: $scheduledAt,
                notes: $request['notes'] ?? null,
                sections: $sections,
            );
        } else {
            $workout = Workout::create([
                'user_id' => $user->getKey(),
                'name' => $request['name'],
                'activity' => Activity::from($request['activity']),
                'scheduled_at' => $scheduledAt,
                'notes' => $request['notes'] ?? null,
            ]);
        }

        return json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout, $user),
            'message' => 'Workout created successfully',
        ]);
    }
}
