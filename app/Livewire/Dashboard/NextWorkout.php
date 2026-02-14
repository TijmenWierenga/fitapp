<?php

namespace App\Livewire\Dashboard;

use App\Models\Workout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NextWorkout extends Component
{
    #[Computed]
    public function nextWorkout(): ?Workout
    {
        return auth()->user()
            ->workouts()
            ->upcoming()
            ->with(['sections.blocks.exercises.exerciseable'])
            ->first();
    }

    #[On('workout-completed')]
    public function refreshNextWorkout(): void
    {
        unset($this->nextWorkout);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.next-workout');
    }
}
