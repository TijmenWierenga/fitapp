<?php

namespace App\Mcp\Tools;

use App\Enums\Fit\GarminExerciseCategory;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class CreateExerciseTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Add a new exercise to the catalog.

        **Before calling this tool**, present the exercise details to the user and ask for confirmation. Include: name, category, level, equipment, muscles, and instructions.

        Provide as much detail as possible — fill in ALL fields (instructions, tips, aliases, description, primary and secondary muscles). A complete exercise entry ensures accurate workload tracking and useful guidance for the user.

        Read the `exercise://muscle-groups` resource for valid muscle group names.
    MARKDOWN;

    /**
     * Determine if the tool should be registered.
     */
    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->isAdmin() ?? false;
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercises,name',
            'category' => 'required|string|in:strength,stretching,plyometrics,cardio',
            'level' => 'required|string|in:beginner,intermediate,expert',
            'force' => 'nullable|string|in:push,pull,static',
            'mechanic' => 'nullable|string|in:compound,isolation',
            'equipment' => 'nullable|string|in:bands,barbell,body only,cable,dumbbell,e-z curl bar,exercise ball,foam roll,kettlebells,machine,medicine ball,other',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|array',
            'instructions.*' => 'string',
            'aliases' => 'nullable|array',
            'aliases.*' => 'string',
            'tips' => 'nullable|array',
            'tips.*' => 'string',
            'primary_muscles' => 'nullable|array',
            'primary_muscles.*' => 'string|exists:muscle_groups,name',
            'secondary_muscles' => 'nullable|array',
            'secondary_muscles.*' => 'string|exists:muscle_groups,name',
            'garmin_exercise_category' => ['nullable', Rule::enum(GarminExerciseCategory::class)],
            'garmin_exercise_name' => 'nullable|integer|min:0',
        ]);

        $hasCategory = isset($validated['garmin_exercise_category']);
        $hasName = isset($validated['garmin_exercise_name']);

        if ($hasCategory !== $hasName) {
            return Response::error('Both garmin_exercise_category and garmin_exercise_name must be provided together.');
        }

        $primaryMuscles = $validated['primary_muscles'] ?? [];
        $secondaryMuscles = $validated['secondary_muscles'] ?? [];

        $overlap = array_intersect($primaryMuscles, $secondaryMuscles);

        if (count($overlap) > 0) {
            return Response::error('Muscle groups cannot appear in both primary and secondary: '.implode(', ', $overlap));
        }

        $exercise = DB::transaction(function () use ($validated, $primaryMuscles, $secondaryMuscles): Exercise {
            $exercise = Exercise::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'category' => $validated['category'],
                'level' => $validated['level'],
                'force' => $validated['force'] ?? null,
                'mechanic' => $validated['mechanic'] ?? null,
                'equipment' => $validated['equipment'] ?? null,
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'] ?? [],
                'aliases' => $validated['aliases'] ?? [],
                'tips' => $validated['tips'] ?? [],
                'garmin_exercise_category' => $validated['garmin_exercise_category'] ?? null,
                'garmin_exercise_name' => $validated['garmin_exercise_name'] ?? null,
            ]);

            if (count($primaryMuscles) > 0) {
                $primaryIds = MuscleGroup::whereIn('name', $primaryMuscles)->pluck('id');
                $exercise->muscleGroups()->attach(
                    $primaryIds->mapWithKeys(fn (int $id): array => [$id => ['load_factor' => 1.0]])->toArray()
                );
            }

            if (count($secondaryMuscles) > 0) {
                $secondaryIds = MuscleGroup::whereIn('name', $secondaryMuscles)->pluck('id');
                $exercise->muscleGroups()->attach(
                    $secondaryIds->mapWithKeys(fn (int $id): array => [$id => ['load_factor' => 0.5]])->toArray()
                );
            }

            return $exercise;
        });

        $exercise->load('muscleGroups');

        return Response::structured([
            'exercise' => [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'slug' => $exercise->slug,
                'category' => $exercise->category,
                'equipment' => $exercise->equipment,
                'level' => $exercise->level,
                'force' => $exercise->force,
                'mechanic' => $exercise->mechanic,
                'garmin_compatible' => $exercise->has_garmin_mapping,
                'description' => $exercise->description,
                'instructions' => $exercise->instructions,
                'aliases' => $exercise->aliases,
                'tips' => $exercise->tips,
                'primary_muscles' => $exercise->muscleGroups
                    ->where('pivot.load_factor', 1.0)
                    ->map(fn (MuscleGroup $mg): array => [
                        'name' => $mg->name,
                        'label' => $mg->label,
                        'load_factor' => (float) $mg->pivot->load_factor,
                    ])->values()->toArray(),
                'secondary_muscles' => $exercise->muscleGroups
                    ->where('pivot.load_factor', 0.5)
                    ->map(fn (MuscleGroup $mg): array => [
                        'name' => $mg->name,
                        'label' => $mg->label,
                        'load_factor' => (float) $mg->pivot->load_factor,
                    ])->values()->toArray(),
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        $muscleGroupEnum = [
            'abdominals', 'abductors', 'adductors', 'biceps', 'calves', 'chest',
            'forearms', 'glutes', 'hamstrings', 'lats', 'lower back', 'middle back',
            'neck', 'quadriceps', 'shoulders', 'traps', 'triceps',
        ];

        return [
            'name' => $schema->string()->description('Unique exercise name (e.g., "Barbell Bench Press")')->required(),
            'category' => $schema->string()->enum(['strength', 'stretching', 'plyometrics', 'cardio'])->description('Exercise category.')->required(),
            'level' => $schema->string()->enum(['beginner', 'intermediate', 'expert'])->description('Difficulty level.')->required(),
            'force' => $schema->string()->enum(['push', 'pull', 'static'])->description('Force type.')->nullable(),
            'mechanic' => $schema->string()->enum(['compound', 'isolation'])->description('Mechanic type.')->nullable(),
            'equipment' => $schema->string()->enum([
                'bands', 'barbell', 'body only', 'cable', 'dumbbell', 'e-z curl bar',
                'exercise ball', 'foam roll', 'kettlebells', 'machine', 'medicine ball', 'other',
            ])->description('Required equipment.')->nullable(),
            'description' => $schema->string()->description('Brief exercise description.')->nullable(),
            'instructions' => $schema->array()->description('Step-by-step instructions.')->nullable(),
            'aliases' => $schema->array()->description('Alternative names for the exercise.')->nullable(),
            'tips' => $schema->array()->description('Performance tips and cues.')->nullable(),
            'primary_muscles' => $schema->array()->description('Primary muscle groups (load factor 1.0). Use names from exercise://muscle-groups resource.')->nullable(),
            'secondary_muscles' => $schema->array()->description('Secondary muscle groups (load factor 0.5). Must not overlap with primary_muscles.')->nullable(),
            'garmin_exercise_category' => $schema->integer()->enum(array_column(GarminExerciseCategory::cases(), 'value'))->description('Garmin FIT exercise category ID. Mapping: 0 = Bench Press, 1 = Calf Raise, 2 = Cardio, 3 = Carry, 4 = Chop, 5 = Core, 6 = Crunch, 7 = Curl, 8 = Deadlift, 9 = Flye, 10 = Hip Raise, 11 = Hip Stability, 12 = Hip Swing, 13 = Hyperextension, 14 = Lateral Raise, 15 = Leg Curl, 16 = Leg Raise, 17 = Lunge, 18 = Olympic Lift, 19 = Plank, 20 = Plyo, 21 = Pull-Up, 22 = Push-Up, 23 = Row, 24 = Shoulder Press, 25 = Shoulder Stability, 26 = Shrug, 27 = Sit-Up, 28 = Squat, 29 = Total Body, 30 = Triceps Extension, 31 = Warm Up, 32 = Run, 65534 = Unknown. Must be provided together with garmin_exercise_name.')->nullable(),
            'garmin_exercise_name' => $schema->integer()->description('Garmin FIT exercise name ID within the category. See garmin_exercises.json for valid IDs per category. Must be provided together with garmin_exercise_category.')->nullable(),
        ];
    }
}
