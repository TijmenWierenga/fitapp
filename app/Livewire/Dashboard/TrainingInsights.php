<?php

namespace App\Livewire\Dashboard;

use App\Services\Training\TrainingAnalyticsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TrainingInsights extends Component
{
    /**
     * @return array{total_completed: int, completion_rate: float, current_streak_days: int}
     */
    #[Computed]
    public function analytics(): array
    {
        return app(TrainingAnalyticsService::class)
            ->getAnalytics(auth()->user(), 4);
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->analytics);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.training-insights');
    }
}
