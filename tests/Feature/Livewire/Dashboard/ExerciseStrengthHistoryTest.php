<?php

use App\Domain\Workload\Enums\HistoryRange;
use App\Livewire\Dashboard\ExerciseStrengthHistory;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('has modal hidden by default', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(ExerciseStrengthHistory::class)
        ->assertSet('showModal', false)
        ->assertSet('exerciseId', null);
});

it('opens modal when show-exercise-history event is dispatched', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_max' => 5,
        'target_weight' => 80.0,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(ExerciseStrengthHistory::class)
        ->dispatch('show-exercise-history', exerciseId: $exercise->id)
        ->assertSet('showModal', true)
        ->assertSet('exerciseId', $exercise->id)
        ->assertSee('Bench Press');
});

it('refreshes data when range is changed', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $exercise = Exercise::factory()->create(['name' => 'Squat']);

    Livewire::actingAs($user)
        ->test(ExerciseStrengthHistory::class)
        ->dispatch('show-exercise-history', exerciseId: $exercise->id)
        ->assertSet('range', HistoryRange::ThreeMonths)
        ->set('range', HistoryRange::OneYear)
        ->assertSet('range', HistoryRange::OneYear);
});

it('shows empty state when no data for selected range', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $exercise = Exercise::factory()->create(['name' => 'Deadlift']);

    Livewire::actingAs($user)
        ->test(ExerciseStrengthHistory::class)
        ->dispatch('show-exercise-history', exerciseId: $exercise->id)
        ->assertSee('No strength data for this exercise');
});
