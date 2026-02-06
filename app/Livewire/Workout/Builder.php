<?php

namespace App\Livewire\Workout;

use App\Enums\Workout\Activity;
use App\Models\Workout;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Builder extends Component
{
    public ?Workout $workout = null;

    public string $name = '';

    public ?string $notes = null;

    public Activity $activity = Activity::Run;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public function mount(?Workout $workout = null): void
    {
        if ($workout && $workout->exists) {
            if (! $workout->canBeEdited()) {
                abort(403, 'Completed workouts cannot be edited.');
            }

            $this->workout = $workout;
            $this->name = $workout->name;
            $this->notes = $workout->notes;
            $this->activity = $workout->activity;
            $this->scheduled_date = $workout->scheduled_at->format('Y-m-d');
            $this->scheduled_time = $workout->scheduled_at->format('H:i');
        } else {
            $this->scheduled_date = now()->format('Y-m-d');
            $this->scheduled_time = now()->format('H:i');
        }
    }

    public function saveWorkout(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
        ]);

        $scheduledAt = "{$this->scheduled_date} {$this->scheduled_time}";

        if ($this->workout && $this->workout->exists && ! $this->workout->fresh()->canBeEdited()) {
            abort(403, 'Completed workouts cannot be edited.');
        }

        if (! $this->workout) {
            $this->workout = new Workout;
            $this->workout->user_id = auth()->id();
        }

        $this->workout->name = $this->name;
        $this->workout->notes = $this->notes;
        $this->workout->activity = $this->activity;
        $this->workout->scheduled_at = $scheduledAt;
        $this->workout->save();

        $this->redirect(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.workout.builder');
    }
}
