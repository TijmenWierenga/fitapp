<?php

use App\Enums\DurationType;
use App\Enums\Intensity;
use App\Enums\StepType;
use App\Enums\WorkoutType;
use App\Livewire\Dashboard\NextWorkout;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('displays the next workout with its steps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workout = Workout::factory()->for($user)->create([
        'name' => 'Interval Training',
        'type' => WorkoutType::Running,
        'scheduled_at' => now()->addHour(),
    ]);

    $workout->steps()->create([
        'order' => 0,
        'type' => StepType::Step,
        'intensity' => Intensity::Warmup,
        'duration_type' => DurationType::Time,
        'duration_value' => 600,
    ]);

    $repeat = $workout->steps()->create([
        'order' => 1,
        'type' => StepType::Repetition,
        'duration_type' => DurationType::RepetitionCount,
        'duration_value' => 5,
    ]);

    $repeat->children()->create([
        'workout_id' => $workout->id,
        'order' => 0,
        'type' => StepType::Step,
        'intensity' => Intensity::Active,
        'duration_type' => DurationType::Distance,
        'duration_value' => 400,
    ]);

    Livewire::test(NextWorkout::class)
        ->assertSee('Interval Training')
        ->assertSee('10m Warmup')
        ->assertSee('5x')
        ->assertSee('0.4km Active');
});
