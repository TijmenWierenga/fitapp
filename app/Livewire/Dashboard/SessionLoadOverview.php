<?php

namespace App\Livewire\Dashboard;

use App\Actions\CalculateWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SessionLoadOverview extends Component
{
    #[Computed]
    public function workloadSummary(): WorkloadSummary
    {
        return app(CalculateWorkload::class)->execute(auth()->user());
    }

    /**
     * @return array<int, array{week: string, load: int}>
     */
    #[Computed]
    public function chartData(): array
    {
        $summary = $this->workloadSummary;
        $data = [];

        if ($summary->sessionLoad !== null) {
            foreach (array_reverse($summary->sessionLoad->previousWeeks) as $week) {
                $data[] = ['week' => "Week {$week->weekOffset}", 'load' => $week->totalLoad];
            }

            $data[] = ['week' => 'This week', 'load' => $summary->sessionLoad->currentWeeklyTotal];
        }

        return $data;
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->workloadSummary, $this->chartData);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.session-load-overview');
    }
}
