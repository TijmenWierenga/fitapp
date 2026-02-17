<?php

namespace App\Livewire\Workout;

use App\Models\Workout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Preview extends Component
{
    public ?Workout $workout = null;

    public bool $showModal = false;

    public bool $showEvaluationModal = false;

    public ?int $rpe = null;

    public ?int $feeling = null;

    #[On('show-workout-preview')]
    public function loadWorkout(int $workoutId): void
    {
        $this->workout = auth()->user()
            ->workouts()
            ->with('sections.blocks.exercises.exerciseable')
            ->findOrFail($workoutId);

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->workout = null;
        $this->reset(['rpe', 'feeling', 'showEvaluationModal']);
        $this->resetValidation();
    }

    #[Computed]
    public function isOverdue(): bool
    {
        if (! $this->workout) {
            return false;
        }

        return ! $this->workout->isCompleted()
            && $this->workout->scheduled_at->isPast()
            && ! $this->workout->scheduled_at->isToday();
    }

    public function openEvaluationModal(): void
    {
        $this->showEvaluationModal = true;
    }

    public function submitEvaluation(): void
    {
        $this->validate([
            'rpe' => ['required', 'integer', 'min:1', 'max:10'],
            'feeling' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'rpe.required' => 'Please rate how hard this workout felt.',
            'rpe.min' => 'RPE must be between 1 and 10.',
            'rpe.max' => 'RPE must be between 1 and 10.',
            'feeling.required' => 'Please rate how you felt during this workout.',
            'feeling.min' => 'Feeling must be between 1 and 5.',
            'feeling.max' => 'Feeling must be between 1 and 5.',
        ]);

        $this->workout->markAsCompleted($this->rpe, $this->feeling);
        $this->showEvaluationModal = false;
        $this->reset(['rpe', 'feeling']);
        $this->workout->refresh();
        $this->dispatch('workout-completed');
    }

    public function cancelEvaluation(): void
    {
        $this->showEvaluationModal = false;
        $this->reset(['rpe', 'feeling']);
        $this->resetValidation();
    }

    #[Computed]
    public function rpeLabel(): string
    {
        return match ($this->rpe) {
            1, 2 => 'Very Easy',
            3, 4 => 'Easy',
            5, 6 => 'Moderate',
            7, 8 => 'Hard',
            9, 10 => 'Maximum Effort',
            default => '',
        };
    }

    public function deleteWorkout(): void
    {
        if ($this->workout->deleteIfAllowed()) {
            $this->dispatch('workout-deleted');
            $this->closeModal();
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.preview');
    }
}
