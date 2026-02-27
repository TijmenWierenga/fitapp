<?php

namespace App\Livewire\Workout;

use App\Actions\RecordPainScores;
use App\Models\Injury;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Workout $workout;

    public bool $showEvaluationModal = false;

    public ?int $rpe = null;

    public ?int $feeling = null;

    /** @var array<int, int|null> */
    public array $painScores = [];

    /** @var Collection<int, Injury>|null */
    public ?Collection $activeInjuries = null;

    public function mount(Workout $workout): void
    {
        if ($workout->user_id !== auth()->id()) {
            abort(403);
        }

        $workout->load('sections.blocks.exercises.exerciseable', 'painScores.injury');

        $this->workout = $workout;
    }

    #[Computed]
    public function isOverdue(): bool
    {
        return ! $this->workout->isCompleted()
            && $this->workout->scheduled_at->isPast()
            && ! $this->workout->scheduled_at->isToday();
    }

    /**
     * @return array{color: string, text: string}
     */
    #[Computed]
    public function statusBadge(): array
    {
        if ($this->workout->isCompleted()) {
            return ['color' => 'green', 'text' => 'Completed'];
        }

        if ($this->isOverdue) {
            return ['color' => 'red', 'text' => 'Overdue'];
        }

        if ($this->workout->scheduled_at->isToday()) {
            return ['color' => 'green', 'text' => 'Today'];
        }

        if ($this->workout->scheduled_at->isTomorrow()) {
            return ['color' => 'blue', 'text' => 'Tomorrow'];
        }

        return ['color' => 'zinc', 'text' => $this->workout->scheduled_at->diffForHumans()];
    }

    public function openEvaluationModal(): void
    {
        $this->activeInjuries = auth()->user()->injuries()->active()->get();
        $this->painScores = $this->activeInjuries
            ->mapWithKeys(fn (Injury $injury): array => [$injury->id => null])
            ->all();

        $this->showEvaluationModal = true;
    }

    public function submitEvaluation(): void
    {
        $this->validate([
            'rpe' => ['required', 'integer', 'min:1', 'max:10'],
            'feeling' => ['required', 'integer', 'min:1', 'max:5'],
            'painScores.*' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'rpe.required' => 'Please rate how hard this workout felt.',
            'rpe.min' => 'RPE must be between 1 and 10.',
            'rpe.max' => 'RPE must be between 1 and 10.',
            'feeling.required' => 'Please rate how you felt during this workout.',
            'feeling.min' => 'Feeling must be between 1 and 5.',
            'feeling.max' => 'Feeling must be between 1 and 5.',
        ]);

        $this->workout->markAsCompleted($this->rpe, $this->feeling);

        // Record pain scores for injuries where a score was provided
        $scores = collect($this->painScores)
            ->filter(fn (?int $score): bool => $score !== null)
            ->all();

        if ($scores !== []) {
            app(RecordPainScores::class)->execute($this->workout, $scores);
            $this->workout->load('painScores.injury');
        }

        $this->showEvaluationModal = false;
        $this->dispatch('workout-completed');
    }

    public function cancelEvaluation(): void
    {
        $this->showEvaluationModal = false;
        $this->reset(['rpe', 'feeling', 'painScores', 'activeInjuries']);
        $this->resetValidation();
    }

    #[Computed]
    public function rpeLabel(): string
    {
        return match ($this->rpe) {
            1, 2 => 'Very Easy',
            3, 4 => 'Easy',
            5, 6 => 'Moderate',
            7, 8 => 'Hard',
            9, 10 => 'Maximum Effort',
            default => '',
        };
    }

    public function deleteWorkout(): void
    {
        if ($this->workout->deleteIfAllowed()) {
            $this->redirect(route('dashboard'));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.show');
    }
}
