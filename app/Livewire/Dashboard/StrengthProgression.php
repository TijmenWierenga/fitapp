<?php

namespace App\Livewire\Dashboard;

use App\Actions\CalculateWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class StrengthProgression extends Component
{
    #[Computed]
    public function workloadSummary(): WorkloadSummary
    {
        return app(CalculateWorkload::class)->execute(auth()->user());
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->workloadSummary);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.strength-progression');
    }
}
