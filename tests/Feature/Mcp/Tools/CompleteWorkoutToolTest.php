<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\CompleteWorkoutTool;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

use function Pest\Laravel\assertDatabaseHas;

it('completes workout successfully', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $workout = Workout::factory()->for($user)->upcoming()->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $response->assertOk()
        ->assertSee('Workout completed successfully')
        ->assertSee('Hard');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $workout->refresh();
    expect($workout->isCompleted())->toBeTrue();
});

it('includes rpe label in response', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 2,
        'feeling' => 5,
    ]);

    $response->assertOk()
        ->assertSee('Very Easy');
});

it('saves completion notes', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => 4,
        'completion_notes' => 'Felt great today!',
    ]);

    $response->assertOk()
        ->assertSee('Felt great today!');

    assertDatabaseHas('workouts', [
        'id' => $workout->id,
        'completion_notes' => 'Felt great today!',
    ]);
});

it('saves injury evaluations', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 6,
        'feeling' => 3,
        'injury_evaluations' => [
            [
                'injury_id' => $injury->id,
                'discomfort_score' => 4,
                'notes' => 'Minor pain during lunges',
            ],
        ],
    ]);

    $response->assertOk();

    assertDatabaseHas('workout_injury_evaluations', [
        'workout_id' => $workout->id,
        'injury_id' => $injury->id,
        'discomfort_score' => 4,
        'notes' => 'Minor pain during lunges',
    ]);
});

it('includes injury evaluations in response', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'body_part' => \App\Enums\BodyPart::Knee,
    ]);
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 6,
        'feeling' => 3,
        'injury_evaluations' => [
            [
                'injury_id' => $injury->id,
                'discomfort_score' => 5,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('injury_evaluations')
        ->assertSee('Knee');
});

it('ignores injury evaluations for other users injuries', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherInjury = Injury::factory()->for($otherUser)->active()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 6,
        'feeling' => 3,
        'injury_evaluations' => [
            [
                'injury_id' => $otherInjury->id,
                'discomfort_score' => 5,
            ],
        ],
    ]);

    $response->assertOk();

    expect(\App\Models\WorkoutInjuryEvaluation::count())->toBe(0);
});

it('fails to complete already completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('already completed');
});

it('fails with RPE out of range', function (int $invalidRpe) {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => $invalidRpe,
        'feeling' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('RPE must be between');
})->with([0, 11, -1]);

it('fails with feeling out of range', function (int $invalidFeeling) {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => $invalidFeeling,
    ]);

    $response->assertHasErrors()
        ->assertSee('Feeling must be between');
})->with([0, 6, -1]);

it('fails to complete workout owned by different user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $workout = Workout::factory()->for($user1)->create();

    $response = WorkoutServer::actingAs($user2)->tool(CompleteWorkoutTool::class, [
        'workout_id' => $workout->id,
        'rpe' => 5,
        'feeling' => 3,
    ]);

    $response->assertHasErrors()
        ->assertSee('Workout not found or access denied');
});
