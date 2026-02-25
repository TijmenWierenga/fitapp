<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NextWorkout extends Component
{
    #[Computed]
    public function upcomingWorkouts(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->workouts()
            ->upcoming()
            ->with(['sections.blocks.exercises.exerciseable'])
            ->limit(3)
            ->get();
    }

    #[On('workout-completed')]
    public function refreshUpcomingWorkouts(): void
    {
        unset($this->upcomingWorkouts);
    }

    #[On('workout-duplicated')]
    public function refreshAfterDuplicate(): void
    {
        unset($this->upcomingWorkouts);
    }

    #[On('workout-deleted')]
    public function refreshAfterDelete(): void
    {
        unset($this->upcomingWorkouts);
    }

    public function deleteWorkout(int $workoutId): void
    {
        $workout = auth()->user()->workouts()->findOrFail($workoutId);

        $workout->deleteIfAllowed();

        unset($this->upcomingWorkouts);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.next-workout');
    }
}
