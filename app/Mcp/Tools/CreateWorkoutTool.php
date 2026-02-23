<?php

namespace App\Mcp\Tools;

use App\Actions\CreateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class CreateWorkoutTool extends Tool
{
    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
        private CreateStructuredWorkout $createStructuredWorkout,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new workout with a scheduled date and time. You MUST include structured sections with blocks and exercises — every workout needs at least one section, each section needs at least one block, and each block needs at least one exercise.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities.

        Dates/times should be in the user's local timezone and will be converted to UTC for storage.

        The `sections` array defines the workout structure. Each section contains blocks, and each block contains exercises with a `type` field (strength, cardio, or duration) plus type-specific fields. See the `block_type` schema for field guidance per type.

        IMPORTANT: Before creating a workout, use `SearchExercisesTool` to find exercises in the catalog and include their `exercise_id` in each block exercise. This links exercises to the catalog for muscle group tracking and Garmin compatibility.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'activity' => ['required', Rule::enum(Activity::class)],
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
            ...WorkoutSchemaBuilder::sectionValidationRules(),
            'sections' => 'required|array|min:1',
            'sections.*.blocks' => 'required|array|min:1',
            'sections.*.blocks.*.exercises' => 'required|array|min:1',
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = $request->user();
        $scheduledAt = CarbonImmutable::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();

        $sections = collect($validated['sections'])
            ->map(fn (array $section): SectionData => SectionData::fromArray($section));

        $workout = $this->createStructuredWorkout->execute(
            user: $user,
            name: $validated['name'],
            activity: Activity::from($validated['activity']),
            scheduledAt: $scheduledAt,
            notes: $validated['notes'] ?? null,
            sections: $sections,
        );

        return Response::structured([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout, $user),
            'message' => 'Workout created successfully',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name/title of the workout (e.g., "Morning Run", "Leg Day")'),
            'activity' => $schema->string()->enum(Activity::class)->description('The activity type.'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional Markdown notes for the workout.')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Required structured workout sections with blocks and exercises.'),
        ];
    }
}
