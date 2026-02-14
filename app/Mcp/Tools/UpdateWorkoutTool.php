<?php

namespace App\Mcp\Tools;

use App\Actions\UpdateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
class UpdateWorkoutTool extends Tool
{
    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
        private UpdateStructuredWorkout $updateStructuredWorkout,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing workout. Only workouts that have not been completed can be updated.

        You can update the name, activity, scheduled time, notes, or sections. Only provide the fields you want to change.

        If `sections` is provided, the existing structure will be replaced entirely with the new sections/blocks/exercises. See the `block_type` schema for field guidance per type.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'activity' => ['sometimes', Rule::enum(Activity::class)],
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string|max:5000',
            ...WorkoutSchemaBuilder::sectionValidationRules(),
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied.');
        }

        if ($user->cannot('update', $workout)) {
            return Response::error('Cannot update a completed workout.');
        }

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['activity'])) {
            $updateData['activity'] = Activity::from($validated['activity']);
        }

        if (isset($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = CarbonImmutable::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        $workout->update($updateData);

        if (isset($validated['sections'])) {
            $sections = collect($validated['sections'])
                ->map(fn (array $section): SectionData => SectionData::fromArray($section));

            $this->updateStructuredWorkout->execute($workout, $sections);
        }

        return Response::structured([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout updated successfully',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to update'),
            'name' => $schema->string()->description('The new name for the workout')->nullable(),
            'activity' => $schema->string()->description('The new activity type (e.g., run, strength, cardio, hiit, bike, pool_swim, hike, yoga, etc.)')->nullable(),
            'scheduled_at' => $schema->string()->description('The new scheduled date and time (in user\'s timezone)')->nullable(),
            'notes' => $schema->string()->description('The new notes for the workout')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Replace entire workout structure with new sections/blocks/exercises. If provided, existing structure is deleted and replaced.')->nullable(),
        ];
    }
}
