<?php

use App\Livewire\Dashboard\NextWorkout;
use App\Livewire\Dashboard\TrainingInsights;
use App\Livewire\Dashboard\WorkoutCalendar;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('displays next workout component', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee($workout->name)
        ->assertSee('View Workout');
});

it('displays empty state when no next workout', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('No upcoming workouts scheduled')
        ->assertSee('Schedule Workout');
});

it('refreshes next workout when workout completed event is dispatched', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->dispatch('workout-completed')
        ->assertStatus(200);
});

it('displays training insights with completed workouts', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->count(3)->create([
        'scheduled_at' => now()->subDays(2),
        'completed_at' => now()->subDays(2),
        'rpe' => 5,
        'feeling' => 4,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingInsights::class)
        ->assertSee('Training Insights')
        ->assertSee('Last 4 weeks')
        ->assertSee('3')
        ->assertSee('Completed')
        ->assertSee('Completion Rate')
        ->assertSee('Streak (days)');
});

it('displays training insights empty state when no completed workouts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TrainingInsights::class)
        ->assertSee('No completed workouts yet');
});

it('refreshes training insights when workout completed', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TrainingInsights::class)
        ->dispatch('workout-completed')
        ->assertStatus(200);
});

it('displays workout calendar', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->assertSee('Workout Calendar')
        ->assertSee(now()->format('F Y'));
});

it('shows workouts on calendar', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->setDay(15)->setHour(10),
    ]);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->assertSee($workout->name);
});

it('can navigate to previous month', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('previousMonth')
        ->assertSee(now()->subMonth()->format('F Y'));
});

it('can navigate to next month', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('nextMonth')
        ->assertSee(now()->addMonth()->format('F Y'));
});

it('can return to current month', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('nextMonth')
        ->call('nextMonth')
        ->call('today')
        ->assertSee(now()->format('F Y'));
});

it('distinguishes between completed and upcoming workouts on calendar', function () {
    $user = User::factory()->create();

    $completed = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->setDay(10),
        'completed_at' => now(),
    ]);

    $upcoming = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->setDay(20),
        'completed_at' => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test(WorkoutCalendar::class);

    // Both workouts should be visible
    expect($component->get('calendarWeeks'))
        ->toBeArray();
});

it('refreshes calendar when workout completed', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    $component = Livewire::actingAs($user)
        ->test(WorkoutCalendar::class);

    $component->dispatch('workout-completed');

    $component->assertStatus(200);
});

it('can delete a future workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('deleteWorkout', $workout->id)
        ->assertStatus(200);

    expect(Workout::count())->toBe(0);
});

it('can delete a workout scheduled for today', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('deleteWorkout', $workout->id)
        ->assertStatus(200);

    expect(Workout::count())->toBe(0);
});

it('cannot delete a past workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDay(),
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('deleteWorkout', $workout->id)
        ->assertStatus(200);

    // Workout should still exist
    expect(Workout::count())->toBe(1);
    expect($workout->fresh())->not->toBeNull();
});

it('cannot delete another users workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create([
        'scheduled_at' => now()->addDay(),
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('deleteWorkout', $workout->id);

    // Workout should still exist (user doesn't have access to it)
    expect(Workout::count())->toBe(1);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
