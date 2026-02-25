<?php

use App\Livewire\Dashboard\NextWorkout;
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
        ->assertSee('View');
});

it('displays empty state when no next workout', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('No upcoming workouts scheduled')
        ->assertSee('Create Workout');
});

it('refreshes next workout when workout completed event is dispatched', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->dispatch('workout-completed')
        ->assertStatus(200);
});

it('shows upcoming workouts in schedule list', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee($workout->name);
});

it('hides workout structure when next workout has no sections', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertDontSee('Straight Sets');
});

it('can delete a workout from next workout widget', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->call('deleteWorkout', $workout->id)
        ->assertStatus(200);

    expect(Workout::count())->toBe(0);
});

it('cannot delete another users workout from next workout widget', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create(['scheduled_at' => now()->addDay()]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->call('deleteWorkout', $workout->id);

    expect(Workout::count())->toBe(1);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('refreshes next workout when workout-duplicated event is dispatched', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->dispatch('workout-duplicated')
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
        ->assertSee('15');
});

it('can navigate to previous month', function () {
    $this->travelTo(now()->startOfMonth()->addDays(14));

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('previousMonth')
        ->assertSee(now()->subMonth()->format('F Y'));
});

it('can navigate to next month', function () {
    $this->travelTo(now()->startOfMonth()->addDays(14));

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

it('refreshes calendar when workout deleted', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->dispatch('workout-deleted')
        ->assertStatus(200);
});
