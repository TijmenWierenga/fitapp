<?php

namespace App\Livewire\Dashboard;

use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class WorkoutCalendar extends Component
{
    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function today(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    #[Computed]
    public function monthName(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    /**
     * @return array<int, array<int, array{date: \Carbon\Carbon, isCurrentMonth: bool, isToday: bool, isPast: bool, workouts: \Illuminate\Support\Collection<int, \App\Models\Workout>}>>
     */
    #[Computed]
    public function calendarWeeks(): array
    {
        $firstDay = Carbon::create($this->year, $this->month, 1);
        $lastDay = $firstDay->copy()->endOfMonth();

        // Get workouts for this month
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Workout> $workoutsModels */
        $workoutsModels = auth()->user()
            ->workouts()
            ->whereBetween('scheduled_at', [$firstDay->startOfMonth(), $lastDay->endOfMonth()])
            ->orderBy('scheduled_at')
            ->get();

        $workouts = $workoutsModels->groupBy(fn (\App\Models\Workout $workout) => $workout->scheduled_at->format('Y-m-d'));

        $weeks = [];
        $currentWeek = [];

        // Start from the beginning of the week
        $startDate = $firstDay->copy()->startOfWeek();
        $endDate = $lastDay->copy()->endOfWeek();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dayWorkouts = $workouts->get($dateKey, collect());

            $currentWeek[] = [
                'date' => $date->copy(),
                'isCurrentMonth' => $date->month === $this->month,
                'isToday' => $date->isToday(),
                'isPast' => $date->isPast() && ! $date->isToday(),
                'workouts' => $dayWorkouts,
            ];

            if ($date->dayOfWeek === Carbon::SATURDAY) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        if (! empty($currentWeek)) {
            $weeks[] = $currentWeek;
        }

        return $weeks;
    }

    #[On('workout-completed')]
    public function refresh(): void
    {
        unset($this->calendarWeeks);
    }

    #[On('workout-duplicated')]
    public function refreshAfterDuplicate(): void
    {
        unset($this->calendarWeeks);
    }

    public function deleteWorkout(int $workoutId): void
    {
        $workout = auth()->user()->workouts()->findOrFail($workoutId);

        $workout->deleteIfAllowed();

        unset($this->calendarWeeks);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.workout-calendar');
    }
}
