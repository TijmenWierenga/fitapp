<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Actions\GetExerciseStrengthHistory;
use App\Domain\Workload\Enums\HistoryRange;
use App\Domain\Workload\Results\ExerciseStrengthHistoryResult;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ExerciseStrengthHistory extends Component
{
    public ?int $exerciseId = null;

    public bool $showModal = false;

    public HistoryRange $range = HistoryRange::ThreeMonths;

    #[On('show-exercise-history')]
    public function loadHistory(int $exerciseId): void
    {
        $this->exerciseId = $exerciseId;
        $this->range = HistoryRange::ThreeMonths;
        $this->showModal = true;
        unset($this->historyResult, $this->chartData);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->exerciseId = null;
        unset($this->historyResult, $this->chartData);
    }

    public function updatedRange(): void
    {
        unset($this->historyResult, $this->chartData);
    }

    #[Computed]
    public function historyResult(): ?ExerciseStrengthHistoryResult
    {
        if ($this->exerciseId === null) {
            return null;
        }

        return app(GetExerciseStrengthHistory::class)->execute(
            user: auth()->user(),
            exerciseId: $this->exerciseId,
            range: $this->range,
        );
    }

    /**
     * @return array<int, array{date: string, maxWeight: float, volume: float, e1rm: float}>
     */
    #[Computed]
    public function chartData(): array
    {
        $result = $this->historyResult;

        if ($result === null || empty($result->points)) {
            return [];
        }

        return array_map(fn ($point): array => [
            'date' => $point->date->format('M j'),
            'maxWeight' => round($point->maxWeight, 1),
            'volume' => round($point->volume, 1),
            'e1rm' => round($point->estimated1RM, 1),
        ], $result->points);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.exercise-strength-history');
    }
}
