<?php

use App\Livewire\Dashboard\CompletedWorkouts;
use App\Livewire\Dashboard\NextWorkout;
use App\Livewire\Dashboard\UpcomingWorkouts;
use App\Livewire\Dashboard\WorkoutCalendar;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertStatus(200);
});

test('dashboard displays all workout components', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSeeLivewire(NextWorkout::class)
        ->assertSeeLivewire(UpcomingWorkouts::class)
        ->assertSeeLivewire(CompletedWorkouts::class)
        ->assertSeeLivewire(WorkoutCalendar::class);
});
