<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class UpcomingWorkouts extends Component
{
    #[Computed]
    public function upcomingWorkouts(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->workouts()
            ->upcoming()
            ->limit(5)
            ->get();
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->upcomingWorkouts);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.upcoming-workouts');
    }
}
