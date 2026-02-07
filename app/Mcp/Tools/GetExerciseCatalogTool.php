<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\Equipment;
use App\Enums\Workout\ExerciseCategory;
use App\Enums\Workout\MovementPattern;
use App\Enums\Workout\MuscleGroup;
use App\Models\Exercise;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class GetExerciseCatalogTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Browse the exercise catalog with optional filters. Filter by category, equipment, movement pattern, muscle group, or search by name.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::enum(ExerciseCategory::class)],
            'equipment' => ['nullable', Rule::enum(Equipment::class)],
            'movement_pattern' => ['nullable', Rule::enum(MovementPattern::class)],
            'muscle_group' => ['nullable', Rule::enum(MuscleGroup::class)],
            'search' => 'nullable|string|max:100',
        ]);

        $query = Exercise::query()->with('muscleLoads');

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (! empty($validated['equipment'])) {
            $query->where('equipment', $validated['equipment']);
        }

        if (! empty($validated['movement_pattern'])) {
            $query->where('movement_pattern', $validated['movement_pattern']);
        }

        if (! empty($validated['muscle_group'])) {
            $muscleGroup = $validated['muscle_group'];
            $query->whereHas('muscleLoads', fn ($q) => $q->where('muscle_group', $muscleGroup));
        }

        if (! empty($validated['search'])) {
            $query->where('name', 'like', "%{$validated['search']}%");
        }

        $exercises = $query->orderBy('name')->get();

        $data = $exercises->map(fn (Exercise $exercise): array => [
            'id' => $exercise->id,
            'name' => $exercise->name,
            'category' => $exercise->category->value,
            'equipment' => $exercise->equipment->value,
            'movement_pattern' => $exercise->movement_pattern->value,
            'muscle_loads' => $exercise->muscleLoads->map(fn ($ml): array => [
                'muscle_group' => $ml->muscle_group->value,
                'role' => $ml->role->value,
                'load_factor' => $ml->load_factor,
            ])->all(),
        ])->all();

        return Response::text(json_encode([
            'success' => true,
            'count' => count($data),
            'exercises' => $data,
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()
                ->enum(array_map(fn (ExerciseCategory $c): string => $c->value, ExerciseCategory::cases()))
                ->description('Filter by exercise category')
                ->nullable(),
            'equipment' => $schema->string()
                ->enum(array_map(fn (Equipment $e): string => $e->value, Equipment::cases()))
                ->description('Filter by equipment type')
                ->nullable(),
            'movement_pattern' => $schema->string()
                ->enum(array_map(fn (MovementPattern $m): string => $m->value, MovementPattern::cases()))
                ->description('Filter by movement pattern')
                ->nullable(),
            'muscle_group' => $schema->string()
                ->enum(array_map(fn (MuscleGroup $m): string => $m->value, MuscleGroup::cases()))
                ->description('Filter by muscle group')
                ->nullable(),
            'search' => $schema->string()
                ->description('Search exercises by name')
                ->nullable(),
        ];
    }
}
