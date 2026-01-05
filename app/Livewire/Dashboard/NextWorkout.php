<?php

namespace App\Livewire\Dashboard;

use App\Models\Workout;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NextWorkout extends Component
{
    #[Computed]
    public function nextWorkout(): ?Workout
    {
        return auth()->user()
            ->workouts()
            ->upcoming()
            ->first();
    }

    public function markAsCompleted(int $workoutId): void
    {
        $workout = auth()->user()->workouts()->findOrFail($workoutId);
        $workout->markAsCompleted();

        $this->dispatch('workout-completed');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.next-workout');
    }
}
