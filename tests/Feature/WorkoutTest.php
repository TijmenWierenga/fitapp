<?php

use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;

it('shows upcoming workouts', function () {
    $user = User::factory()->create();

    // Create past, upcoming, and completed workouts
    Workout::factory()->for($user)->create(['scheduled_at' => now()->subDay()]);
    $upcoming1 = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);
    $upcoming2 = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDays(2)]);
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDays(3), 'completed_at' => now()]);

    $upcomingWorkouts = $user->workouts()->upcoming()->get();

    expect($upcomingWorkouts)->toHaveCount(2)
        ->and($upcomingWorkouts->first()->id)->toBe($upcoming1->id)
        ->and($upcomingWorkouts->last()->id)->toBe($upcoming2->id);
});

it('shows completed workouts', function () {
    $user = User::factory()->create();

    Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => null]);
    $completed1 = Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => now()]);
    $completed2 = Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => now()->subDay()]);

    $completedWorkouts = $user->workouts()->completed()->get();

    expect($completedWorkouts)->toHaveCount(2)
        ->and($completedWorkouts->first()->id)->toBe($completed1->id);
});

it('shows overdue workouts', function () {
    $user = User::factory()->create();

    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);
    $overdue1 = Workout::factory()->for($user)->create(['scheduled_at' => now()->subDay()]);
    $overdue2 = Workout::factory()->for($user)->create(['scheduled_at' => now()->subDays(2)]);
    Workout::factory()->for($user)->create(['scheduled_at' => now()->subDays(3), 'completed_at' => now()]);

    $overdueWorkouts = $user->workouts()->overdue()->get();

    expect($overdueWorkouts)->toHaveCount(2)
        ->and($overdueWorkouts->first()->id)->toBe($overdue1->id)
        ->and($overdueWorkouts->last()->id)->toBe($overdue2->id);
});

it('can mark workout as completed with evaluation', function () {
    $workout = Workout::factory()->create(['completed_at' => null]);

    expect($workout->isCompleted())->toBeFalse();

    $workout->markAsCompleted(rpe: 7, feeling: 4);

    $workout->refresh();

    expect($workout->isCompleted())->toBeTrue()
        ->and($workout->completed_at)->not->toBeNull()
        ->and($workout->rpe)->toBe(7)
        ->and($workout->feeling)->toBe(4);
});

it('checks if workout is completed', function () {
    $incomplete = Workout::factory()->create(['completed_at' => null]);
    $complete = Workout::factory()->create(['completed_at' => now()]);

    expect($incomplete->isCompleted())->toBeFalse()
        ->and($complete->isCompleted())->toBeTrue();
});

it('can delete a future workout', function () {
    $workout = Workout::factory()->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    expect($workout->canBeDeleted())->toBeTrue();

    $result = $workout->deleteIfAllowed();

    expect($result)->toBeTrue()
        ->and(Workout::find($workout->id))->toBeNull();
});

it('can delete a workout scheduled for today', function () {
    $workout = Workout::factory()->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    expect($workout->canBeDeleted())->toBeTrue();

    $result = $workout->deleteIfAllowed();

    expect($result)->toBeTrue()
        ->and(Workout::find($workout->id))->toBeNull();
});

it('can delete a completed workout', function () {
    $workout = Workout::factory()->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => now(),
    ]);

    expect($workout->canBeDeleted())->toBeTrue();

    $result = $workout->deleteIfAllowed();

    expect($result)->toBeTrue()
        ->and(Workout::find($workout->id))->toBeNull();
});

it('can delete a past workout', function () {
    $workout = Workout::factory()->create([
        'scheduled_at' => now()->subDay(),
        'completed_at' => null,
    ]);

    expect($workout->canBeDeleted())->toBeTrue();

    $result = $workout->deleteIfAllowed();

    expect($result)->toBeTrue()
        ->and(Workout::find($workout->id))->toBeNull();
});

it('can delete a completed past workout', function () {
    $workout = Workout::factory()->create([
        'scheduled_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    expect($workout->canBeDeleted())->toBeTrue();

    $result = $workout->deleteIfAllowed();

    expect($result)->toBeTrue()
        ->and(Workout::find($workout->id))->toBeNull();
});

it('deleting a workout removes related exerciseables', function () {
    $workout = Workout::factory()->create(['scheduled_at' => now()->addDay()]);
    $section = Section::factory()->for($workout)->create();
    $block = Block::factory()->for($section)->create();

    $strengthExercise = StrengthExercise::factory()->create();
    $cardioExercise = CardioExercise::factory()->create();

    BlockExercise::factory()->for($block)->create([
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);
    BlockExercise::factory()->for($block)->create([
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardioExercise->id,
    ]);

    $workout->deleteIfAllowed();

    expect(StrengthExercise::find($strengthExercise->id))->toBeNull()
        ->and(CardioExercise::find($cardioExercise->id))->toBeNull()
        ->and(Section::where('workout_id', $workout->id)->count())->toBe(0)
        ->and(Block::where('section_id', $section->id)->count())->toBe(0)
        ->and(BlockExercise::where('block_id', $block->id)->count())->toBe(0);
});
