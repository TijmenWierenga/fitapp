<?php

use App\Actions\UpdateStructuredWorkout;
use App\DataTransferObjects\Workout\BlockData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\SectionData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseType;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;

it('replaces existing structure with new sections', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create();

    // Create initial structure
    $section = Section::factory()->for($workout)->create(['name' => 'Old Section']);
    $block = Block::factory()->for($section)->create();
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $newSections = collect([
        new SectionData(
            name: 'New Section',
            order: 0,
            blocks: collect([
                new BlockData(
                    blockType: BlockType::StraightSets,
                    order: 0,
                    exercises: collect([
                        new ExerciseData(
                            name: 'Squat',
                            order: 0,
                            type: ExerciseType::Strength,
                            exerciseable: new StrengthExerciseData(
                                targetSets: 5,
                                targetRepsMax: 5,
                                targetWeight: 100.0,
                            ),
                        ),
                    ]),
                ),
            ]),
        ),
    ]);

    app(UpdateStructuredWorkout::class)->execute($workout, $newSections);

    $this->assertDatabaseMissing('sections', ['name' => 'Old Section']);
    $this->assertDatabaseMissing('strength_exercises', ['id' => $strength->id]);

    $this->assertDatabaseHas('sections', ['workout_id' => $workout->id, 'name' => 'New Section']);
    $this->assertDatabaseHas('block_exercises', ['name' => 'Squat']);
    $this->assertDatabaseHas('strength_exercises', ['target_sets' => 5, 'target_weight' => 100.0]);
});

it('deletes orphaned polymorphic exerciseable records', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create();

    $section = Section::factory()->for($workout)->create();
    $block = Block::factory()->for($section)->create();
    $strength1 = StrengthExercise::factory()->create();
    $strength2 = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength1->id,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength2->id,
    ]);

    app(UpdateStructuredWorkout::class)->execute($workout, collect());

    $this->assertDatabaseMissing('strength_exercises', ['id' => $strength1->id]);
    $this->assertDatabaseMissing('strength_exercises', ['id' => $strength2->id]);
    $this->assertDatabaseMissing('sections', ['workout_id' => $workout->id]);
});

it('handles workout with no existing structure', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create();

    $sections = collect([
        new SectionData(
            name: 'First Section',
            order: 0,
            blocks: collect(),
        ),
    ]);

    app(UpdateStructuredWorkout::class)->execute($workout, $sections);

    $this->assertDatabaseHas('sections', ['workout_id' => $workout->id, 'name' => 'First Section']);
});
