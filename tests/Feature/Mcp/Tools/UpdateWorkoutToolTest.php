<?php

use App\Enums\Workout\Activity;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\UpdateWorkoutTool;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseHas;

it('updates workout name successfully', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Old Name']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertSee('New Name')
        ->assertSee('Workout updated successfully');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'name' => 'New Name',
    ]);
});

it('updates workout activity successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['activity' => Activity::Run]);

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'activity' => 'strength',
    ]);

    $response->assertOk();

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'activity' => 'strength',
    ]);
});

it('updates workout scheduled_at successfully', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'scheduled_at' => '2026-02-01 08:00:00',
    ]);

    $response->assertOk();

    $workout->refresh();
    expect($workout->scheduled_at->timezone->getName())->toBe('UTC');
});

it('updates workout notes successfully', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['notes' => 'Old notes']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'notes' => 'Updated notes',
    ]);

    $response->assertOk();

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'notes' => 'Updated notes',
    ]);
});

it('fails to update workout owned by different user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $workout = Workout::factory()->for($user1)->create();

    $response = WorkoutServer::actingAs($user2)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'name' => 'Hacked Name',
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});

it('fails to update completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'name' => 'New Name',
    ]);

    $response->assertHasErrors()
        ->assertSee('Cannot update completed workouts');
});

it('fails with non-existent workout_id', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => 99999,
        'name' => 'Test',
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});

it('replaces workout structure when sections provided', function () {
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

    $response = WorkoutServer::actingAs($user)->tool(UpdateWorkoutTool::class, [
        'workout_id' => $workout->id,
        'sections' => [
            [
                'name' => 'New Section',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'circuit',
                        'order' => 0,
                        'rounds' => 3,
                        'exercises' => [
                            [
                                'name' => 'Burpee',
                                'order' => 0,
                                'type' => 'duration',
                                'target_duration' => 45,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('New Section')
        ->assertSee('Burpee')
        ->assertSee('circuit');

    // Old structure should be gone
    $this->assertDatabaseMissing('sections', ['name' => 'Old Section']);
    // New structure should exist
    assertDatabaseHas('sections', ['name' => 'New Section']);
    assertDatabaseHas('block_exercises', ['name' => 'Burpee']);
});
