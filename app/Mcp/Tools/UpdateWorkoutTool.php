<?php

namespace App\Mcp\Tools;

use App\Actions\UpdateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
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

        If `sections` is provided, the existing structure will be replaced entirely with the new sections/blocks/exercises.

        ## Block Types & Fields

        Only set the fields listed for each block type — omit all others:

        - **straight_sets**: _(no block-level fields)_ — exercises define their own sets/reps/rest
        - **circuit**: rounds, rest_between_exercises, rest_between_rounds
        - **superset**: rounds, rest_between_rounds
        - **interval**: rounds, work_interval, rest_interval — for distance-based intervals, omit work_interval (exercise distance/pace defines the work)
        - **amrap**: time_cap
        - **for_time**: rounds, time_cap
        - **emom**: rounds (= number of intervals), work_interval (= seconds per interval)
        - **distance_duration**: _(no block-level fields)_ — exercise distance/duration defines the work
        - **rest**: _(no block-level fields)_
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'activity' => ['sometimes', Rule::enum(Activity::class)],
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string|max:5000',
            'sections' => 'sometimes|array',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.order' => 'required|integer|min:0',
            'sections.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks' => 'sometimes|array',
            'sections.*.blocks.*.block_type' => ['required', Rule::enum(BlockType::class)],
            'sections.*.blocks.*.order' => 'required|integer|min:0',
            'sections.*.blocks.*.rounds' => 'nullable|integer|min:1',
            'sections.*.blocks.*.rest_between_exercises' => 'nullable|integer|min:0',
            'sections.*.blocks.*.rest_between_rounds' => 'nullable|integer|min:0',
            'sections.*.blocks.*.time_cap' => 'nullable|integer|min:0',
            'sections.*.blocks.*.work_interval' => 'nullable|integer|min:0',
            'sections.*.blocks.*.rest_interval' => 'nullable|integer|min:0',
            'sections.*.blocks.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks.*.exercises' => 'sometimes|array',
            'sections.*.blocks.*.exercises.*.name' => 'required|string|max:255',
            'sections.*.blocks.*.exercises.*.order' => 'required|integer|min:0',
            'sections.*.blocks.*.exercises.*.type' => 'required|in:strength,cardio,duration',
            'sections.*.blocks.*.exercises.*.notes' => 'nullable|string|max:5000',
            // Strength exercise fields
            'sections.*.blocks.*.exercises.*.target_sets' => ['nullable', 'integer', 'min:1', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength'],
            'sections.*.blocks.*.exercises.*.target_reps_min' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength'],
            'sections.*.blocks.*.exercises.*.target_reps_max' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength'],
            'sections.*.blocks.*.exercises.*.target_weight' => ['nullable', 'numeric', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength'],
            'sections.*.blocks.*.exercises.*.target_tempo' => ['nullable', 'string', 'max:20', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength'],
            'sections.*.blocks.*.exercises.*.rest_after' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength'],
            // Cardio exercise fields
            'sections.*.blocks.*.exercises.*.target_duration' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio,duration'],
            'sections.*.blocks.*.exercises.*.target_distance' => ['nullable', 'numeric', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            'sections.*.blocks.*.exercises.*.target_pace_min' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            'sections.*.blocks.*.exercises.*.target_pace_max' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            'sections.*.blocks.*.exercises.*.target_heart_rate_zone' => ['nullable', 'integer', 'min:1', 'max:5', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            'sections.*.blocks.*.exercises.*.target_heart_rate_min' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            'sections.*.blocks.*.exercises.*.target_heart_rate_max' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            'sections.*.blocks.*.exercises.*.target_power' => ['nullable', 'integer', 'min:0', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,cardio'],
            // Shared: strength + duration
            'sections.*.blocks.*.exercises.*.target_rpe' => ['nullable', 'numeric', 'min:1', 'max:10', 'prohibited_unless:sections.*.blocks.*.exercises.*.type,strength,duration'],
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            Gate::forUser($user)->authorize('update', $workout);
        } catch (AuthorizationException) {
            return Response::error('Cannot update completed workouts');
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

        return Response::text(json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout updated successfully',
        ]));
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

    /**
     * Get the tool's output schema.
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()->required(),
            'workout' => $schema->object($this->schemaBuilder->workoutOutputSchema())->required(),
            'message' => $schema->string()->required(),
        ];
    }
}
