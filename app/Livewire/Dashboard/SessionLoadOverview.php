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
     * @return array<int, array{week: string, load: int, sessions: int}>
     */
    #[Computed]
    public function chartData(): array
    {
        $summary = $this->workloadSummary;
        $data = [];

        if ($summary->sessionLoad !== null) {
            foreach (array_reverse($summary->sessionLoad->previousWeeks) as $week) {
                $data[] = ['week' => "Week {$week->weekOffset}", 'load' => $week->totalLoad, 'sessions' => $week->sessionCount];
            }

            $data[] = ['week' => 'This week', 'load' => $summary->sessionLoad->currentWeeklyTotal, 'sessions' => $summary->sessionLoad->currentSessionCount];
        }

        return $data;
    }

    /**
     * @return array<int, array{date: string, acute: float, chronic: float}>
     */
    #[Computed]
    public function ewmaChartData(): array
    {
        $ewma = $this->workloadSummary->ewmaLoad;

        if ($ewma === null) {
            return [];
        }

        $points = $ewma->dailyPoints;
        $total = count($points);

        // Sample every 3rd day plus the last point to keep ~14 labels
        $sampled = [];

        foreach ($points as $index => $point) {
            if ($index % 3 === 0 || $index === $total - 1) {
                $sampled[] = [
                    'date' => date('M j', strtotime($point->date)),
                    'acute' => $point->acuteLoad,
                    'chronic' => $point->chronicLoad,
                ];
            }
        }

        return $sampled;
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->workloadSummary, $this->chartData, $this->ewmaChartData);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.session-load-overview');
    }
}
