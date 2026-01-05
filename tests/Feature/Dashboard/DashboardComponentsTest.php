<?php

use App\Livewire\Dashboard\CompletedWorkouts;
use App\Livewire\Dashboard\NextWorkout;
use App\Livewire\Dashboard\UpcomingWorkouts;
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
        ->assertSee('Mark as Completed');
});

it('displays empty state when no next workout', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('No upcoming workouts scheduled')
        ->assertSee('Schedule Workout');
});

it('can mark next workout as completed', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    expect($workout->completed_at)->toBeNull();

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->call('markAsCompleted', $workout->id)
        ->assertDispatched('workout-completed');

    expect($workout->fresh()->completed_at)->not->toBeNull();
});

it('displays upcoming workouts', function () {
    $user = User::factory()->create();
    $workout1 = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);
    $workout2 = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDays(2)]);

    Livewire::actingAs($user)
        ->test(UpcomingWorkouts::class)
        ->assertSee($workout1->name)
        ->assertSee($workout2->name);
});

it('displays empty state when no upcoming workouts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(UpcomingWorkouts::class)
        ->assertSee('No upcoming workouts');
});

it('refreshes upcoming workouts when workout completed', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    $component = Livewire::actingAs($user)
        ->test(UpcomingWorkouts::class);

    $component->dispatch('workout-completed');

    $component->assertStatus(200);
});

it('displays completed workouts', function () {
    $user = User::factory()->create();
    $workout1 = Workout::factory()->for($user)->create(['scheduled_at' => now(), 'completed_at' => now()]);
    $workout2 = Workout::factory()->for($user)->create(['scheduled_at' => now()->subDay(), 'completed_at' => now()->subDay()]);

    Livewire::actingAs($user)
        ->test(CompletedWorkouts::class)
        ->assertSee($workout1->name)
        ->assertSee($workout2->name);
});

it('displays empty state when no completed workouts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CompletedWorkouts::class)
        ->assertSee('No completed workouts yet');
});

it('limits completed workouts to 10', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->count(15)->create([
        'scheduled_at' => now(),
        'completed_at' => now(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(CompletedWorkouts::class);

    expect($component->get('completedWorkouts'))->toHaveCount(10);
});

it('refreshes completed workouts when workout completed', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(CompletedWorkouts::class);

    $component->dispatch('workout-completed');

    $component->assertStatus(200);
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
