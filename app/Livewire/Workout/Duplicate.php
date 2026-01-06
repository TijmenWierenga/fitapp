<?php

namespace App\Livewire\Workout;

use App\Models\Workout;
use Livewire\Attributes\On;
use Livewire\Component;

class Duplicate extends Component
{
    public ?Workout $workout = null;

    public bool $showModal = false;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    #[On('duplicate-workout')]
    public function openModal(int $workoutId): void
    {
        $this->workout = auth()->user()->workouts()->findOrFail($workoutId);

        // Pre-fill with current scheduled date/time
        $this->scheduled_date = $this->workout->scheduled_at->format('Y-m-d');
        $this->scheduled_time = $this->workout->scheduled_at->format('H:i');

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->workout = null;
        $this->reset(['scheduled_date', 'scheduled_time']);
    }

    public function save(): void
    {
        $this->validate([
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required'],
        ]);

        $scheduledAt = $this->scheduled_date.' '.$this->scheduled_time;

        $this->workout->duplicate(new \DateTime($scheduledAt));

        $this->dispatch('workout-duplicated');

        $this->closeModal();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.duplicate');
    }
}
