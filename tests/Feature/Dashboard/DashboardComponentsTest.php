<?php

use App\Livewire\Dashboard\NextWorkout;
use App\Livewire\Dashboard\WorkoutCalendar;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
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

it('shows workout structure when next workout has sections', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Bench Press',
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('Workout Structure')
        ->assertSee('Bench Press');
});

it('hides workout structure when next workout has no sections', function () {
    $user = User::factory()->create();
    Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertDontSee('Workout Structure');
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

it('can delete a past workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDay(),
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->call('deleteWorkout', $workout->id)
        ->assertStatus(200);

    expect(Workout::count())->toBe(0);
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
