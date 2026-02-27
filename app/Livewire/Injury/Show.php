<?php

declare(strict_types=1);

namespace App\Livewire\Injury;

use App\Models\Injury;
use App\Models\WorkoutInjuryPainScore;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Injury $injury;

    public function mount(Injury $injury): void
    {
        if ($injury->user_id !== Auth::id()) {
            abort(403);
        }

        $injury->load('injuryReports');

        $this->injury = $injury;
    }

    #[Computed]
    public function latestPainScore(): ?WorkoutInjuryPainScore
    {
        return $this->injury->painScores()->latest()->first();
    }

    #[Computed]
    public function statusLabel(): string
    {
        if ($this->injury->ended_at === null) {
            return 'Active';
        }

        if ($this->injury->ended_at->greaterThanOrEqualTo(now()->subDays(30))) {
            return 'Recovering';
        }

        return 'Healed';
    }

    #[Computed]
    public function statusColor(): string
    {
        if ($this->injury->ended_at === null) {
            return 'red';
        }

        if ($this->injury->ended_at->greaterThanOrEqualTo(now()->subDays(30))) {
            return 'amber';
        }

        return 'green';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.injury.show');
    }
}
