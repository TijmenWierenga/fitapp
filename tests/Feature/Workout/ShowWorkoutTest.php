<?php

use App\Enums\Workout\StepKind;
use App\Livewire\Workout\Show;
use App\Models\Step;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

// Authorization tests
it('allows viewing own workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('workouts.show', $workout))
        ->assertOk();
});

it('prevents viewing another users workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('workouts.show', $workout))
        ->assertForbidden();
});

it('requires authentication', function () {
    $workout = Workout::factory()->create();

    $this->get(route('workouts.show', $workout))
        ->assertRedirect(route('login'));
});

// Display tests
it('displays workout details', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Morning Run')
        ->assertSee($workout->scheduled_at->format('l, F j, Y'));
});

it('displays all workout steps', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();
    Step::factory()->count(10)->for($workout)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertStatus(200);

    expect($workout->rootSteps)->toHaveCount(10);
});

it('displays nested steps in repeat blocks', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();
    $repeatStep = Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Repeat,
        'repeat_count' => 3,
    ]);
    Step::factory()->for($workout)->create([
        'parent_step_id' => $repeatStep->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Repeat 3x');
});

// Status badge tests
it('shows completed badge for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Completed');
});

it('shows overdue badge for past uncompleted workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Overdue');
});

it('shows today badge for workout scheduled today', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Today');
});

it('shows tomorrow badge for workout scheduled tomorrow', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Tomorrow');
});

// Action tests
it('can mark workout as completed', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('markAsCompleted')
        ->assertDispatched('workout-completed');

    expect($workout->fresh()->completed_at)->not->toBeNull();
});

it('shows edit button for editable workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Edit Workout');
});

it('hides edit button for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Edit Workout');
});

it('can delete a deletable workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('deleteWorkout')
        ->assertRedirect(route('dashboard'));

    expect(Workout::count())->toBe(0);
});

it('cannot delete a completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('deleteWorkout');

    expect(Workout::count())->toBe(1);
});

it('shows duplicate button', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Duplicate');
});

it('shows mark as completed button for today workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Mark as Completed');
});

it('hides mark as completed button for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Mark as Completed');
});

it('hides mark as completed button for future workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDays(2),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Mark as Completed');
});

it('shows delete button for deletable workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Delete');
});

it('hides delete button for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Delete');
});
