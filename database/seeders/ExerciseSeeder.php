<?php

namespace Database\Seeders;

use App\Enums\BodyPart;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExerciseSeeder extends Seeder
{
    /**
     * Muscle name → BodyPart mapping from the wrkout dataset.
     *
     * @var array<string, BodyPart>
     */
    private const MUSCLE_BODY_PARTS = [
        'abdominals' => BodyPart::Core,
        'hamstrings' => BodyPart::Hamstring,
        'calves' => BodyPart::Calf,
        'shoulders' => BodyPart::Shoulder,
        'adductors' => BodyPart::Hip,
        'glutes' => BodyPart::Glutes,
        'quadriceps' => BodyPart::Quadriceps,
        'biceps' => BodyPart::Biceps,
        'forearms' => BodyPart::Forearm,
        'abductors' => BodyPart::Hip,
        'triceps' => BodyPart::Triceps,
        'chest' => BodyPart::Chest,
        'lower back' => BodyPart::LowerBack,
        'traps' => BodyPart::UpperBack,
        'middle back' => BodyPart::UpperBack,
        'lats' => BodyPart::UpperBack,
        'neck' => BodyPart::Neck,
    ];

    /**
     * Muscle name → human-readable label.
     *
     * @var array<string, string>
     */
    private const MUSCLE_LABELS = [
        'abdominals' => 'Abdominals',
        'hamstrings' => 'Hamstrings',
        'calves' => 'Calves',
        'shoulders' => 'Shoulders',
        'adductors' => 'Adductors',
        'glutes' => 'Glutes',
        'quadriceps' => 'Quadriceps',
        'biceps' => 'Biceps',
        'forearms' => 'Forearms',
        'abductors' => 'Abductors',
        'triceps' => 'Triceps',
        'chest' => 'Chest',
        'lower back' => 'Lower Back',
        'traps' => 'Traps',
        'middle back' => 'Middle Back',
        'lats' => 'Lats',
        'neck' => 'Neck',
    ];

    public function run(): void
    {
        $this->seedMuscleGroups();
        $this->seedExercises();
    }

    private function seedMuscleGroups(): void
    {
        foreach (self::MUSCLE_BODY_PARTS as $name => $bodyPart) {
            MuscleGroup::updateOrCreate(
                ['name' => $name],
                [
                    'label' => self::MUSCLE_LABELS[$name],
                    'body_part' => $bodyPart,
                ],
            );
        }
    }

    private function seedExercises(): void
    {
        $path = database_path('data/exercises.json');
        $exercises = json_decode(file_get_contents($path), true);

        $muscleGroups = MuscleGroup::all()->keyBy('name');

        DB::transaction(function () use ($exercises, $muscleGroups): void {
            foreach ($exercises as $data) {
                $exercise = Exercise::updateOrCreate(
                    ['name' => $data['name']],
                    [
                        'force' => $data['force'] ?? null,
                        'level' => $data['level'],
                        'mechanic' => $data['mechanic'] ?? null,
                        'equipment' => $data['equipment'] ?? null,
                        'category' => $data['category'],
                        'instructions' => $data['instructions'] ?? [],
                        'aliases' => $data['aliases'] ?? null,
                        'description' => $data['description'] ?? null,
                        'tips' => $data['tips'] ?? null,
                    ],
                );

                $pivotData = [];

                foreach ($data['primaryMuscles'] ?? [] as $muscle) {
                    if ($muscleGroup = $muscleGroups->get($muscle)) {
                        $pivotData[$muscleGroup->id] = ['load_factor' => 1.0];
                    }
                }

                foreach ($data['secondaryMuscles'] ?? [] as $muscle) {
                    if ($muscleGroup = $muscleGroups->get($muscle)) {
                        $pivotData[$muscleGroup->id] = ['load_factor' => 0.5];
                    }
                }

                $exercise->muscleGroups()->sync($pivotData);
            }
        });
    }
}
