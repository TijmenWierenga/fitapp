<?php

namespace App\Livewire\Workout;

use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required'],
        ]);

        $scheduledAt = $this->scheduled_date.' '.$this->scheduled_time;

        auth()->user()->workouts()->create([
            'name' => $this->name,
            'scheduled_at' => $scheduledAt,
        ]);

        $this->redirect('/dashboard');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.workout.create');
    }
}
