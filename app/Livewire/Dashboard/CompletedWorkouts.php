<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CompletedWorkouts extends Component
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Workout>
     */
    #[Computed]
    public function completedWorkouts(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->workouts()
            ->completed()
            ->limit(10)
            ->get();
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->completedWorkouts);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.completed-workouts');
    }
}
