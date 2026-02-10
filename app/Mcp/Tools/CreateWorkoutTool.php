<?php

namespace App\Mcp\Tools;

use App\Actions\CreateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
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
        Create a new workout with a scheduled date and time. Optionally include structured sections with blocks and exercises.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities.

        Dates/times should be in the user's local timezone and will be converted to UTC for storage.

        ## Structured Workout

        You can optionally provide a `sections` array to create a fully structured workout with sections, blocks, and exercises.

        Each section contains blocks, and each block contains exercises with a `type` field (strength, cardio, or duration) plus type-specific fields.

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
            'name' => 'required|string|max:255',
            'activity' => ['required', Rule::enum(Activity::class)],
            'scheduled_at' => 'required|date',
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
        $scheduledAt = CarbonImmutable::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();

        if (! empty($validated['sections'])) {
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
        } else {
            $workout = Workout::create([
                'user_id' => $user->getKey(),
                'name' => $validated['name'],
                'activity' => Activity::from($validated['activity']),
                'scheduled_at' => $scheduledAt,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        return Response::text(json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout, $user),
            'message' => 'Workout created successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name/title of the workout (e.g., "Morning Run", "Leg Day")'),
            'activity' => $schema->string()->description('The activity type (e.g., run, strength, cardio, hiit, bike, pool_swim, hike, yoga, etc.)'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional Markdown notes for the workout.')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Optional structured workout sections with blocks and exercises.')->nullable(),
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
