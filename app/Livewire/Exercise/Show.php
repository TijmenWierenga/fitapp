<?php

namespace App\Livewire\Exercise;

use App\Models\Exercise;
use Livewire\Component;

class Show extends Component
{
    public Exercise $exercise;

    public function mount(Exercise $exercise): void
    {
        $exercise->load(['primaryMuscles', 'secondaryMuscles']);

        $this->exercise = $exercise;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.exercise.show');
    }
}
