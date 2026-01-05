<?php

namespace App\Livewire\Workout;

use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $scheduled_at = '';

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
        ]);

        auth()->user()->workouts()->create([
            'name' => $this->name,
            'scheduled_at' => $this->scheduled_at,
        ]);

        $this->redirect('/dashboard');
    }

    public function render()
    {
        return view('livewire.workout.create');
    }
}
