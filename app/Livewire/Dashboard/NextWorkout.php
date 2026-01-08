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
            ->with(['rootSteps.children'])
            ->upcoming()
            ->first();
    }

    public function markAsCompleted(int $workoutId): void
    {
        $workout = auth()->user()->workouts()->findOrFail($workoutId);
        $workout->markAsCompleted();

        $this->dispatch('workout-completed');
    }

    #[On('mark-workout-complete')]
    public function markWorkoutComplete(int $workoutId): void
    {
        $this->markAsCompleted($workoutId);
        unset($this->nextWorkout);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.next-workout');
    }
}
