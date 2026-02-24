<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\CreateWorkoutTool;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('creates a workout successfully', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Morning Run',
        'activity' => 'run',
        'scheduled_at' => '2026-01-26 07:00:00',
        'notes' => 'Easy pace',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'distance_duration',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Easy Run',
                                'order' => 0,
                                'type' => 'cardio',
                                'target_duration' => 1800,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('Morning Run')
        ->assertSee('run')
        ->assertSee('Workout created successfully');

    assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'name' => 'Morning Run',
        'activity' => 'run',
        'notes' => 'Easy pace',
    ]);
});

it('creates a workout without notes', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Strength Training',
        'activity' => 'strength',
        'scheduled_at' => '2026-01-27 18:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Bench Press',
                                'order' => 0,
                                'type' => 'strength',
                                'target_sets' => 3,
                                'target_reps_max' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('Strength Training');

    assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'name' => 'Strength Training',
        'notes' => null,
    ]);
});

it('converts user timezone to UTC for storage', function () {
    $user = User::factory()->withTimezone('America/New_York')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Evening HIIT',
        'activity' => 'hiit',
        'scheduled_at' => '2026-01-26 19:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'circuit',
                        'order' => 0,
                        'rounds' => 3,
                        'exercises' => [
                            [
                                'name' => 'Burpees',
                                'order' => 0,
                                'type' => 'strength',
                                'target_reps_max' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk();

    $workout = $user->workouts()->first();
    expect($workout->scheduled_at->timezone->getName())->toBe('UTC');
});

it('fails with invalid activity', function (string $invalidActivity) {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => $invalidActivity,
        'scheduled_at' => '2026-01-26 07:00:00',
    ]);

    $response->assertHasErrors()
        ->assertSee('The selected activity is invalid');
})->with(['invalid', 'swimming', 'cycling']);

it('fails with empty activity', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => '',
        'scheduled_at' => '2026-01-26 07:00:00',
    ]);

    $response->assertHasErrors()
        ->assertSee('activity');
});

it('fails with invalid scheduled_at', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => 'run',
        'scheduled_at' => 'not-a-date',
    ]);

    $response->assertHasErrors()
        ->assertSee('valid date');
});

it('trims empty notes to null', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Test Workout',
        'activity' => 'run',
        'scheduled_at' => '2026-01-26 07:00:00',
        'notes' => '   ',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'distance_duration',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Easy Run',
                                'order' => 0,
                                'type' => 'cardio',
                                'target_duration' => 1200,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk();

    assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'notes' => null,
    ]);
});

it('creates a structured workout with sections, blocks, and exercises', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Structured Strength',
        'activity' => 'strength',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Warm-up',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'distance_duration',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Light Jog',
                                'order' => 0,
                                'type' => 'cardio',
                                'target_duration' => 300,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Main',
                'order' => 1,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Squat',
                                'order' => 0,
                                'type' => 'strength',
                                'target_sets' => 4,
                                'target_reps_max' => 8,
                                'target_weight' => 100.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('Structured Strength')
        ->assertSee('Warm-up')
        ->assertSee('Main')
        ->assertSee('Light Jog')
        ->assertSee('Squat');

    assertDatabaseHas('sections', ['name' => 'Warm-up']);
    assertDatabaseHas('sections', ['name' => 'Main']);
    assertDatabaseHas('block_exercises', ['name' => 'Light Jog']);
    assertDatabaseHas('block_exercises', ['name' => 'Squat']);
    assertDatabaseHas('strength_exercises', ['target_sets' => 4, 'target_reps_max' => 8]);
    assertDatabaseHas('cardio_exercises', ['target_duration' => 300]);
});

it('rejects strength-only fields on a cardio exercise', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Invalid Workout',
        'activity' => 'cardio',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Run',
                                'order' => 0,
                                'type' => 'cardio',
                                'target_duration' => 600,
                                'target_sets' => 3,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertHasErrors()
        ->assertSee('target_sets');
});

it('rejects cardio-only fields on a strength exercise', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Invalid Workout',
        'activity' => 'strength',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Bench Press',
                                'order' => 0,
                                'type' => 'strength',
                                'target_sets' => 3,
                                'target_distance' => 5.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertHasErrors()
        ->assertSee('target_distance');
});

it('rejects target_rpe on a cardio exercise', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Invalid Workout',
        'activity' => 'cardio',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'distance_duration',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Run',
                                'order' => 0,
                                'type' => 'cardio',
                                'target_duration' => 600,
                                'target_rpe' => 7.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertHasErrors()
        ->assertSee('target_rpe');
});

it('creates a for_time workout with rounds', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'CrossFit WOD',
        'activity' => 'hiit',
        'scheduled_at' => '2026-02-10 09:00:00',
        'sections' => [
            [
                'name' => 'WOD',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'for_time',
                        'order' => 0,
                        'rounds' => 3,
                        'time_cap' => 900,
                        'exercises' => [
                            [
                                'name' => 'Thruster',
                                'order' => 0,
                                'type' => 'strength',
                                'target_reps_max' => 15,
                                'target_weight' => 43.0,
                            ],
                            [
                                'name' => 'Box Jump',
                                'order' => 1,
                                'type' => 'strength',
                                'target_reps_max' => 15,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('CrossFit WOD')
        ->assertSee('for_time');

    assertDatabaseHas('blocks', [
        'block_type' => 'for_time',
        'rounds' => 3,
        'time_cap' => 900,
    ]);
});

it('creates an EMOM workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => '10min EMOM',
        'activity' => 'hiit',
        'scheduled_at' => '2026-02-10 09:00:00',
        'sections' => [
            [
                'name' => 'EMOM',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'emom',
                        'order' => 0,
                        'rounds' => 10,
                        'work_interval' => 60,
                        'exercises' => [
                            [
                                'name' => 'Power Clean',
                                'order' => 0,
                                'type' => 'strength',
                                'target_reps_max' => 3,
                                'target_weight' => 70.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertSee('10min EMOM')
        ->assertSee('emom');

    assertDatabaseHas('blocks', [
        'block_type' => 'emom',
        'rounds' => 10,
        'work_interval' => 60,
    ]);
});

it('allows target_duration on a duration exercise', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Plank Workout',
        'activity' => 'strength',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [
                            [
                                'name' => 'Plank',
                                'order' => 0,
                                'type' => 'duration',
                                'target_duration' => 60,
                                'target_rpe' => 6.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk();
    assertDatabaseHas('duration_exercises', ['target_duration' => 60]);
});

it('fails when sections are missing', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Empty Workout',
        'activity' => 'run',
        'scheduled_at' => '2026-02-10 08:00:00',
    ]);

    $response->assertHasErrors()
        ->assertSee('sections');
});

it('fails when sections array is empty', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'Empty Workout',
        'activity' => 'run',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [],
    ]);

    $response->assertHasErrors()
        ->assertSee('sections');
});

it('fails when a section has no blocks', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'No Blocks',
        'activity' => 'run',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [],
            ],
        ],
    ]);

    $response->assertHasErrors()
        ->assertSee('blocks');
});

it('fails when a block has no exercises', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateWorkoutTool::class, [
        'name' => 'No Exercises',
        'activity' => 'run',
        'scheduled_at' => '2026-02-10 08:00:00',
        'sections' => [
            [
                'name' => 'Main',
                'order' => 0,
                'blocks' => [
                    [
                        'block_type' => 'straight_sets',
                        'order' => 0,
                        'exercises' => [],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertHasErrors()
        ->assertSee('exercises');
});
