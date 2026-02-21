<?php

namespace App\Ai\Tools;

use App\Models\Exercise;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchExercisesTool implements Tool
{
    public function description(): string
    {
        return <<<'TEXT'
        Search and filter the exercise catalog. Returns exercises with muscle group mappings so you can pick appropriate exercise_id values for structured workouts.

        At least one of query or muscle_group is required. Use query to search by name, muscle_group to filter by target muscle. Combine filters for precise results.
        TEXT;
    }

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
            'garmin_compatible' => $schema->boolean()->description('Filter by Garmin FIT exercise mapping availability.')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of results to return (default: 20, max: 50)')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $query = $request['query'] ?? null;
        $muscleGroup = $request['muscle_group'] ?? null;

        if ($query === null && $muscleGroup === null) {
            return json_encode(['error' => 'At least one of query or muscle_group is required.']);
        }

        $limit = min($request['limit'] ?? 20, 50);
        $garminCompatible = $request['garmin_compatible'] ?? null;

        $searchQuery = Exercise::search($query ?? '')
            ->when($request->has('category'), fn ($search) => $search->where('category', $request['category']))
            ->when($request->has('equipment'), fn ($search) => $search->where('equipment', $request['equipment']))
            ->when($request->has('level'), fn ($search) => $search->where('level', $request['level']))
            ->query(fn (Builder $builder) => $builder
                ->when($muscleGroup, fn (Builder $q) => $q->whereHas(
                    'muscleGroups',
                    fn (Builder $mg) => $mg->where('name', $muscleGroup),
                ))
                ->when($garminCompatible === true, fn (Builder $q) => $q->whereNotNull('garmin_exercise_category'))
                ->when($garminCompatible === false, fn (Builder $q) => $q->whereNull('garmin_exercise_category'))
                ->with('muscleGroups')
            );

        $exercises = $searchQuery->take($limit)->get();

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

        return json_encode([
            'count' => $exerciseData->count(),
            'exercises' => $exerciseData->toArray(),
        ]);
    }
}
