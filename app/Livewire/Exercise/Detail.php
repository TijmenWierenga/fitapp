<?php

namespace App\Livewire\Exercise;

use App\Models\Exercise;
use Livewire\Attributes\On;
use Livewire\Component;

class Detail extends Component
{
    public ?Exercise $exercise = null;

    public bool $showModal = false;

    #[On('show-exercise-detail')]
    public function loadExercise(int $exerciseId): void
    {
        $this->exercise = Exercise::with(['primaryMuscles', 'secondaryMuscles'])->findOrFail($exerciseId);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->exercise = null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.exercise.detail');
    }
}
