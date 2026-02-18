<?php

use App\Enums\BodyPart;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetWorkloadTool;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\Injury;
use App\Models\MuscleGroup;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;

it('returns empty workload for user with no completed workouts', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(GetWorkloadTool::class);

    $response->assertOk()
        ->assertSee('"session_load": null')
        ->assertSee('"muscle_group_volume": []')
        ->assertSee('"strength_progression": []')
        ->assertSee('"unlinked_exercise_count": 0')
        ->assertSee('"data_span_days": 0');
});

it('returns session load data for workouts with duration', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'duration' => 3600,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkloadTool::class);

    $response->assertOk()
        ->assertSee('"current_weekly_total":')
        ->assertSee('"current_session_count":')
        ->assertSee('"monotony":')
        ->assertSee('"strain":');
});

it('returns muscle group volume data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'rpe' => 7,
        'feeling' => 4,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10, 'target_rpe' => 7.0]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkloadTool::class);

    $response->assertOk()
        ->assertSee('"muscle_group": "chest"')
        ->assertSee('"current_week_sets":')
        ->assertSee('"trend":');
});

it('includes active injuries', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->active()->create([
        'user_id' => $user->id,
        'body_part' => BodyPart::Knee,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkloadTool::class);

    $response->assertOk()
        ->assertSee('"body_part": "knee"');
});

it('counts unlinked exercises', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'rpe' => 7,
        'feeling' => 4,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => null,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetWorkloadTool::class);

    $response->assertOk()
        ->assertSee('"unlinked_exercise_count": 1')
        ->assertSee('not linked to the exercise library');
});
