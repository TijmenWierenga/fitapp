<?php

namespace App\Livewire\Workout;

use App\Data\CompleteWorkoutData;
use App\Data\InjuryEvaluationData;
use App\Models\Injury;
use App\Models\Workout;
use App\Services\Workout\WorkoutService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Complete extends Component
{
    public ?Workout $workout = null;

    public bool $showModal = false;

    public ?int $rpe = null;

    public ?int $feeling = null;

    public ?string $completionNotes = null;

    /** @var array<int, array{discomfort_score: int|null, notes: string|null}> */
    public array $injuryEvaluations = [];

    #[On('mark-workout-complete')]
    public function openModal(int $workoutId): void
    {
        $this->workout = auth()->user()->workouts()->findOrFail($workoutId);

        $this->injuryEvaluations = [];
        foreach ($this->activeInjuries as $injury) {
            $this->injuryEvaluations[$injury->id] = [
                'discomfort_score' => null,
                'notes' => null,
            ];
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->workout = null;
        $this->reset(['rpe', 'feeling', 'completionNotes', 'injuryEvaluations']);
        $this->resetValidation();
    }

    /**
     * @return Collection<int, Injury>
     */
    #[Computed]
    public function activeInjuries(): Collection
    {
        return auth()->user()->activeInjuries;
    }

    public function setInjuryDiscomfort(int $injuryId, ?int $score): void
    {
        if (isset($this->injuryEvaluations[$injuryId])) {
            $this->injuryEvaluations[$injuryId]['discomfort_score'] = $score;
        }
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

    public function submit(WorkoutService $workoutService): void
    {
        $this->validate([
            'rpe' => ['required', 'integer', 'min:1', 'max:10'],
            'feeling' => ['required', 'integer', 'min:1', 'max:5'],
            'completionNotes' => ['nullable', 'string', 'max:5000'],
            'injuryEvaluations.*.discomfort_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'injuryEvaluations.*.notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'rpe.required' => 'Please rate how hard this workout felt.',
            'rpe.min' => 'RPE must be between 1 and 10.',
            'rpe.max' => 'RPE must be between 1 and 10.',
            'feeling.required' => 'Please rate how you felt during this workout.',
            'feeling.min' => 'Feeling must be between 1 and 5.',
            'feeling.max' => 'Feeling must be between 1 and 5.',
        ]);

        $injuryEvaluationDtos = [];
        foreach ($this->injuryEvaluations as $injuryId => $evaluation) {
            if ($evaluation['discomfort_score'] !== null || $evaluation['notes'] !== null) {
                $injuryEvaluationDtos[] = new InjuryEvaluationData(
                    injuryId: $injuryId,
                    discomfortScore: $evaluation['discomfort_score'],
                    notes: $evaluation['notes'],
                );
            }
        }

        $data = new CompleteWorkoutData(
            rpe: $this->rpe,
            feeling: $this->feeling,
            completionNotes: $this->completionNotes,
            injuryEvaluations: $injuryEvaluationDtos,
        );

        $workoutService->complete(auth()->user(), $this->workout, $data);

        $this->dispatch('workout-completed');

        $this->closeModal();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.complete');
    }
}
