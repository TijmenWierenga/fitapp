<?php

namespace App\Livewire\Workout;

use App\Models\Workout;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Workout $workout;

    public function mount(Workout $workout): void
    {
        if ($workout->user_id !== auth()->id()) {
            abort(403);
        }

        $this->workout = $workout->load(['rootSteps.children']);
    }

    #[Computed]
    public function isOverdue(): bool
    {
        return ! $this->workout->isCompleted()
            && $this->workout->scheduled_at->isPast()
            && ! $this->workout->scheduled_at->isToday();
    }

    /**
     * @return array{color: string, text: string}
     */
    #[Computed]
    public function statusBadge(): array
    {
        if ($this->workout->isCompleted()) {
            return ['color' => 'green', 'text' => 'Completed'];
        }

        if ($this->isOverdue) {
            return ['color' => 'red', 'text' => 'Overdue'];
        }

        if ($this->workout->scheduled_at->isToday()) {
            return ['color' => 'green', 'text' => 'Today'];
        }

        if ($this->workout->scheduled_at->isTomorrow()) {
            return ['color' => 'blue', 'text' => 'Tomorrow'];
        }

        return ['color' => 'zinc', 'text' => $this->workout->scheduled_at->diffForHumans()];
    }

    public function markAsCompleted(): void
    {
        $this->workout->markAsCompleted();
        $this->dispatch('workout-completed');
    }

    public function deleteWorkout(): void
    {
        if ($this->workout->deleteIfAllowed()) {
            $this->redirect(route('dashboard'));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.show');
    }
}
