<?php

namespace App\Mcp\Tools;

use App\Models\Exercise;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchExercisesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Search and filter the exercise catalog. Returns exercises with their muscle group mappings so you can pick appropriate `exercise_id` values for structured workouts.

        At least one of `query` or `muscle_group` is required.

        **Usage tips:**
        - Use `query` to search by exercise name (e.g., "bench press", "squat")
        - Use `muscle_group` to find exercises targeting a specific muscle (e.g., "chest", "hamstrings")
        - Combine filters for precise results (e.g., query="press" + equipment="dumbbell")
        - Primary muscles (load_factor 1.0) receive full training volume; secondary (0.5) receive half
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'muscle_group' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:strength,stretching,plyometrics,cardio',
            'equipment' => 'nullable|string|max:255',
            'level' => 'nullable|string|in:beginner,intermediate,expert',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $validated['query'] ?? null;
        $muscleGroup = $validated['muscle_group'] ?? null;

        if ($query === null && $muscleGroup === null) {
            return Response::error('At least one of `query` or `muscle_group` is required.');
        }

        $limit = $validated['limit'] ?? 20;

        $searchQuery = Exercise::search($query ?? '')
            ->when(isset($validated['category']), fn ($search) => $search->where('category', $validated['category']))
            ->when(isset($validated['equipment']), fn ($search) => $search->where('equipment', $validated['equipment']))
            ->when(isset($validated['level']), fn ($search) => $search->where('level', $validated['level']))
            ->query(fn (Builder $builder) => $builder
                ->when($muscleGroup, fn (Builder $q) => $q->whereHas(
                    'muscleGroups',
                    fn (Builder $mg) => $mg->where('name', $muscleGroup),
                ))
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

        return Response::structured([
            'count' => $exerciseData->count(),
            'exercises' => $exerciseData->toArray(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Text search on exercise name (e.g., "bench press", "squat")')->nullable(),
            'muscle_group' => $schema->string()->description('Filter by muscle group name (e.g., "chest", "hamstrings", "quadriceps")')->nullable(),
            'category' => $schema->string()->description('Filter by category: strength, stretching, plyometrics, cardio')->nullable(),
            'equipment' => $schema->string()->description('Filter by equipment type (e.g., "barbell", "dumbbell", "body only", "machine")')->nullable(),
            'level' => $schema->string()->description('Filter by difficulty: beginner, intermediate, expert')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of results to return (default: 20, max: 50)')->nullable(),
        ];
    }
}
