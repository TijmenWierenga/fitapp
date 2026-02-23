<?php

namespace App\Ai\Tools;

use App\Actions\CreateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Mcp\Tools\WorkoutResponseFormatter;
use App\Mcp\Tools\WorkoutSchemaBuilder;
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
        Create a new workout with a scheduled date and time. You MUST include structured sections with blocks and exercises — every workout needs at least one section, each section needs at least one block, and each block needs at least one exercise.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities.

        Dates/times should be in the user's local timezone. The `sections` array defines the workout structure with warm-up, main work, and cool-down sections.

        IMPORTANT: Before creating a workout, use `SearchExercisesTool` to find exercises in the catalog and include their `exercise_id` in each block exercise. This links exercises to the catalog for muscle group tracking and Garmin compatibility.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name/title of the workout'),
            'activity' => $schema->string()->enum(Activity::class)->description('The activity type.'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional Markdown notes for the workout.')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Required structured workout sections with blocks and exercises.'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $scheduledAt = CarbonImmutable::parse($request['scheduled_at'], $user->getTimezoneObject())->utc();

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

        return json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout, $user),
            'message' => 'Workout created successfully',
        ]);
    }
}
