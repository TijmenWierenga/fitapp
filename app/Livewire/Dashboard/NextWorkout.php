<?php

namespace App\Livewire\Dashboard;

use App\Models\Workout;
use App\Services\Workout\WorkoutEstimator;
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

    #[Computed]
    public function estimatedTotalDistance(): int
    {
        if (! $this->nextWorkout) {
            return 0;
        }

        return app(WorkoutEstimator::class)->estimateDistance($this->nextWorkout);
    }

    #[Computed]
    public function estimatedTotalDuration(): int
    {
        if (! $this->nextWorkout) {
            return 0;
        }

        return app(WorkoutEstimator::class)->estimateDuration($this->nextWorkout);
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
