<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\BlockType;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ObjectType;

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
        return $this->schema->object([
            'block_type' => $this->schema->string()->enum(BlockType::class)->description('Block type: straight_sets, circuit, superset, interval, amrap, for_time, emom, distance_duration, or rest')->required(),
            'order' => $this->schema->integer()->description('Display order (0-based)')->required(),
            'rounds' => $this->schema->integer()->description('Number of rounds')->nullable(),
            'rest_between_exercises' => $this->schema->integer()->description('Rest between exercises in seconds')->nullable(),
            'rest_between_rounds' => $this->schema->integer()->description('Rest between rounds in seconds')->nullable(),
            'time_cap' => $this->schema->integer()->description('Time cap in seconds')->nullable(),
            'work_interval' => $this->schema->integer()->description('Work interval duration in seconds')->nullable(),
            'rest_interval' => $this->schema->integer()->description('Rest interval duration in seconds')->nullable(),
            'notes' => $this->schema->string()->description('Optional notes for the block')->nullable(),
            'exercises' => $this->schema->array()->items($this->exercise())->description('Exercises within this block')->nullable(),
        ]);
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

    public function exercise(): ObjectType
    {
        return $this->schema->object([
            'name' => $this->schema->string()->description('Exercise name (e.g., "Barbell Squat", "Treadmill Run")')->required(),
            'order' => $this->schema->integer()->description('Display order (0-based)')->required(),
            'type' => $this->schema->string()->enum(['strength', 'cardio', 'duration'])->description('Exercise type: strength, cardio, or duration')->required(),
            'notes' => $this->schema->string()->description('Optional notes for the exercise')->nullable(),

            // Strength fields
            'target_sets' => $this->schema->integer()->description('Number of sets (strength only)')->nullable(),
            'target_reps_min' => $this->schema->integer()->description('Minimum reps per set (strength only)')->nullable(),
            'target_reps_max' => $this->schema->integer()->description('Maximum reps per set (strength only)')->nullable(),
            'target_weight' => $this->schema->number()->description('Target weight in kilograms (strength only)')->nullable(),
            'target_tempo' => $this->schema->string()->description('Tempo notation, e.g. "3-1-1-0" for eccentric-pause-concentric-pause in seconds (strength only)')->nullable(),
            'rest_after' => $this->schema->integer()->description('Rest after exercise in seconds (strength only)')->nullable(),

            // Cardio fields
            'target_distance' => $this->schema->number()->description('Target distance in kilometers (cardio only)')->nullable(),
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
