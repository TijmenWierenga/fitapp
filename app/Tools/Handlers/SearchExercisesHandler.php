<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\Exercise;
use App\Tools\Input\SearchExercisesInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;

class SearchExercisesHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Text search on exercise name (e.g., "bench press", "squat")')->nullable(),
            'muscle_group' => $schema->string()->enum([
                'abdominals', 'abductors', 'adductors', 'biceps', 'calves', 'chest',
                'forearms', 'glutes', 'hamstrings', 'lats', 'lower back', 'middle back',
                'neck', 'quadriceps', 'shoulders', 'traps', 'triceps',
            ])->description('Filter by muscle group name.')->nullable(),
            'category' => $schema->string()->enum(['strength', 'stretching', 'plyometrics', 'cardio'])->description('Filter by category.')->nullable(),
            'equipment' => $schema->string()->enum([
                'bands', 'barbell', 'body only', 'cable', 'dumbbell', 'e-z curl bar',
                'exercise ball', 'foam roll', 'kettlebells', 'machine', 'medicine ball', 'other',
            ])->description('Filter by equipment type.')->nullable(),
            'level' => $schema->string()->enum(['beginner', 'intermediate', 'expert'])->description('Filter by difficulty.')->nullable(),
            'garmin_compatible' => $schema->boolean()->description('Filter by Garmin FIT exercise mapping availability. When true, only exercises that can be exported with Garmin exercise IDs are returned.')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of results to return (default: 20, max: 50)')->nullable(),
        ];
    }

    public function execute(SearchExercisesInput $input): ToolResult
    {
        if ($input->query === null && $input->muscleGroup === null) {
            return ToolResult::error('At least one of `query` or `muscle_group` is required.');
        }

        $searchQuery = Exercise::search($input->query ?? '')
            ->when($input->has('category'), fn ($search) => $search->where('category', $input->category))
            ->when($input->has('equipment'), fn ($search) => $search->where('equipment', $input->equipment))
            ->when($input->has('level'), fn ($search) => $search->where('level', $input->level))
            ->query(fn (Builder $builder) => $builder
                ->when($input->muscleGroup, fn (Builder $q) => $q->whereHas(
                    'muscleGroups',
                    fn (Builder $mg) => $mg->where('name', $input->muscleGroup),
                ))
                ->when($input->garminCompatible === true, fn (Builder $q) => $q->whereNotNull('garmin_exercise_category'))
                ->when($input->garminCompatible === false, fn (Builder $q) => $q->whereNull('garmin_exercise_category'))
                ->with('muscleGroups')
            );

        $exercises = $searchQuery->take($input->limit)->get();

        $exerciseData = $exercises->map(fn (Exercise $exercise): array => [
            'id' => $exercise->id,
            'name' => $exercise->name,
            'category' => $exercise->category,
            'equipment' => $exercise->equipment,
            'level' => $exercise->level,
            'force' => $exercise->force,
            'mechanic' => $exercise->mechanic,
            'garmin_compatible' => $exercise->has_garmin_mapping,
            'primary_muscles' => $exercise->muscleGroups
                ->where('pivot.load_factor', 1.0)
                ->map(fn ($mg): array => [
                    'name' => $mg->name,
                    'label' => $mg->label,
                    'load_factor' => (float) $mg->pivot->load_factor,
                ])->values()->toArray(),
            'secondary_muscles' => $exercise->muscleGroups
                ->where('pivot.load_factor', 0.5)
                ->map(fn ($mg): array => [
                    'name' => $mg->name,
                    'label' => $mg->label,
                    'load_factor' => (float) $mg->pivot->load_factor,
                ])->values()->toArray(),
        ]);

        return ToolResult::success([
            'count' => $exerciseData->count(),
            'exercises' => $exerciseData->toArray(),
        ]);
    }
}
