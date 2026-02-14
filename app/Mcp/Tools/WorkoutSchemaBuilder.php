<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\BlockType;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\Validation\Rule;

class WorkoutSchemaBuilder
{
    public function __construct(
        private readonly JsonSchemaTypeFactory $schema,
    ) {}

    public function section(): ObjectType
    {
        return $this->schema->object([
            'name' => $this->schema->string()->description('Section name (e.g., "Warm-Up", "Main Work", "Cool-Down")')->required(),
            'order' => $this->schema->integer()->description('Display order (0-based)')->required(),
            'notes' => $this->schema->string()->description('Optional notes for the section')->nullable(),
            'blocks' => $this->schema->array()->items($this->block())->description('Blocks within this section')->nullable(),
        ]);
    }

    public function block(): ObjectType
    {
        $fieldGuide = self::blockTypeFieldGuide();

        return $this->schema->object([
            'block_type' => $this->schema->string()->enum(BlockType::class)->description("Block type. Each type uses specific fields — only set the fields listed for the chosen type. {$fieldGuide}")->required(),
            'order' => $this->schema->integer()->description('Display order (0-based)')->required(),
            'rounds' => $this->schema->integer()->description('Number of rounds/intervals. Used by: circuit, superset, interval, for_time, emom. For emom, this is the number of intervals (e.g., 10 = 10-minute EMOM).')->nullable(),
            'rest_between_exercises' => $this->schema->integer()->description('Rest between exercises in seconds. Used by: circuit only.')->nullable(),
            'rest_between_rounds' => $this->schema->integer()->description('Rest between rounds in seconds. Used by: circuit, superset.')->nullable(),
            'time_cap' => $this->schema->integer()->description('Time cap in seconds. Used by: amrap, for_time.')->nullable(),
            'work_interval' => $this->schema->integer()->description('Work interval duration in seconds. Used by: interval (work period per rep), emom (seconds available each minute). For distance-based intervals, omit this — the work is defined by exercise distance/pace.')->nullable(),
            'rest_interval' => $this->schema->integer()->description('Rest interval duration in seconds. Used by: interval only.')->nullable(),
            'notes' => $this->schema->string()->description('Optional notes for the block')->nullable(),
            'exercises' => $this->schema->array()->items($this->exercise())->description('Exercises within this block')->nullable(),
        ]);
    }

    public static function blockTypeFieldGuide(): string
    {
        $lines = [];

        foreach (BlockType::fieldGuide() as $type => $fields) {
            $fieldList = $fields ? implode(', ', $fields) : '(none)';
            $lines[] = "{$type}: {$fieldList}";
        }

        return 'Fields per type: '.implode('; ', $lines).'.';
    }

    /**
     * Output schema for a full workout response (used by Create, Update, Get tools).
     *
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function workoutOutputSchema(): array
    {
        $exerciseOutput = $this->schema->object([
            'id' => $this->schema->integer()->required(),
            'name' => $this->schema->string()->required(),
            'order' => $this->schema->integer()->required(),
            'type' => $this->schema->string()->required(),
            'exercise_id' => $this->schema->integer()->nullable(),
            'notes' => $this->schema->string()->nullable(),
            'target_sets' => $this->schema->integer()->nullable(),
            'target_reps_min' => $this->schema->integer()->nullable(),
            'target_reps_max' => $this->schema->integer()->nullable(),
            'target_weight' => $this->schema->number()->nullable(),
            'target_rpe' => $this->schema->number()->nullable(),
            'target_tempo' => $this->schema->string()->nullable(),
            'rest_after' => $this->schema->integer()->nullable(),
            'target_duration' => $this->schema->integer()->nullable(),
            'target_distance' => $this->schema->number()->nullable(),
            'target_pace_min' => $this->schema->integer()->nullable(),
            'target_pace_max' => $this->schema->integer()->nullable(),
            'target_heart_rate_zone' => $this->schema->integer()->nullable(),
            'target_heart_rate_min' => $this->schema->integer()->nullable(),
            'target_heart_rate_max' => $this->schema->integer()->nullable(),
            'target_power' => $this->schema->integer()->nullable(),
        ]);

        $blockOutput = $this->schema->object([
            'id' => $this->schema->integer()->required(),
            'block_type' => $this->schema->string()->required(),
            'order' => $this->schema->integer()->required(),
            'rounds' => $this->schema->integer()->nullable(),
            'rest_between_exercises' => $this->schema->integer()->nullable(),
            'rest_between_rounds' => $this->schema->integer()->nullable(),
            'time_cap' => $this->schema->integer()->nullable(),
            'work_interval' => $this->schema->integer()->nullable(),
            'rest_interval' => $this->schema->integer()->nullable(),
            'notes' => $this->schema->string()->nullable(),
            'exercises' => $this->schema->array()->items($exerciseOutput)->required(),
        ]);

        $sectionOutput = $this->schema->object([
            'id' => $this->schema->integer()->required(),
            'name' => $this->schema->string()->required(),
            'order' => $this->schema->integer()->required(),
            'notes' => $this->schema->string()->nullable(),
            'blocks' => $this->schema->array()->items($blockOutput)->required(),
        ]);

        return [
            'id' => $this->schema->integer()->required(),
            'name' => $this->schema->string()->required(),
            'activity' => $this->schema->string()->required(),
            'scheduled_at' => $this->schema->string()->description('ISO 8601 datetime in user timezone')->required(),
            'completed' => $this->schema->boolean()->required(),
            'completed_at' => $this->schema->string()->nullable(),
            'rpe' => $this->schema->integer()->nullable(),
            'rpe_label' => $this->schema->string()->nullable(),
            'feeling' => $this->schema->integer()->nullable(),
            'notes' => $this->schema->string()->nullable(),
            'sections' => $this->schema->array()->items($sectionOutput)->required(),
        ];
    }

    /**
     * Shared validation rules for the sections/blocks/exercises structure.
     *
     * @return array<string, mixed>
     */
    public static function sectionValidationRules(): array
    {
        return [
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
            'sections.*.blocks.*.exercises.*.exercise_id' => 'nullable|integer|exists:exercises,id',
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
        ];
    }

    public function exercise(): ObjectType
    {
        return $this->schema->object([
            'name' => $this->schema->string()->description('Exercise name (e.g., "Barbell Squat", "Treadmill Run")')->required(),
            'order' => $this->schema->integer()->description('Display order (0-based)')->required(),
            'type' => $this->schema->string()->enum(['strength', 'cardio', 'duration'])->description('Exercise type. strength: target_sets, target_reps_min/max, target_weight, target_tempo, rest_after, target_rpe. cardio: target_distance, target_pace_min/max, target_heart_rate_zone, target_heart_rate_min/max, target_power, target_duration. duration: target_duration, target_rpe.')->required(),
            'exercise_id' => $this->schema->integer()->description('Optional ID from the exercise library. Links to muscle group data for workload tracking.')->nullable(),
            'notes' => $this->schema->string()->description('Optional notes for the exercise')->nullable(),

            // Strength fields
            'target_sets' => $this->schema->integer()->description('Number of sets (strength only). In circuit/interval/emom blocks, the block controls repetition — omit this and use block rounds instead.')->nullable(),
            'target_reps_min' => $this->schema->integer()->description('Minimum reps per set (strength only)')->nullable(),
            'target_reps_max' => $this->schema->integer()->description('Maximum reps per set (strength only)')->nullable(),
            'target_weight' => $this->schema->number()->description('Target weight in kilograms (strength only)')->nullable(),
            'target_tempo' => $this->schema->string()->description('Tempo notation, e.g. "3-1-1-0" for eccentric-pause-concentric-pause in seconds (strength only)')->nullable(),
            'rest_after' => $this->schema->integer()->description('Rest after exercise in seconds (strength only). In circuit/interval blocks, the block controls rest — omit this and use block rest fields instead.')->nullable(),

            // Cardio fields
            'target_distance' => $this->schema->number()->description('Target distance in meters (cardio only)')->nullable(),
            'target_pace_min' => $this->schema->integer()->description('Minimum pace in seconds per kilometer (cardio only)')->nullable(),
            'target_pace_max' => $this->schema->integer()->description('Maximum pace in seconds per kilometer (cardio only)')->nullable(),
            'target_heart_rate_zone' => $this->schema->integer()->description('Target heart rate zone, 1–5 (cardio only)')->nullable(),
            'target_heart_rate_min' => $this->schema->integer()->description('Minimum heart rate in bpm (cardio only)')->nullable(),
            'target_heart_rate_max' => $this->schema->integer()->description('Maximum heart rate in bpm (cardio only)')->nullable(),
            'target_power' => $this->schema->integer()->description('Target power output in watts (cardio only)')->nullable(),

            // Shared: cardio + duration
            'target_duration' => $this->schema->integer()->description('Target duration in seconds (cardio and duration only)')->nullable(),

            // Shared: strength + duration
            'target_rpe' => $this->schema->number()->description('Rate of perceived exertion, 1–10 scale (strength and duration only)')->nullable(),
        ]);
    }
}
