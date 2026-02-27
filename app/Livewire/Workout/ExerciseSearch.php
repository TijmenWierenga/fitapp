<?php

namespace App\Livewire\Workout;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ExerciseSearch extends Component
{
    #[Locked]
    public int $targetSectionIndex = 0;

    #[Locked]
    public int $targetBlockIndex = 0;

    public bool $showModal = false;

    /** search | freeform | configure */
    public string $step = 'search';

    public string $query = '';

    public ?string $muscleGroupFilter = null;

    // Selected exercise (from catalogue or free-form)
    public ?int $selectedExerciseId = null;

    public string $selectedName = '';

    public string $selectedType = 'strength';

    /** @var array<int, string> */
    public array $selectedMuscleGroups = [];

    // Configure step — training parameters
    public ?int $targetSets = null;

    public ?int $targetRepsMin = null;

    public ?int $targetRepsMax = null;

    public ?float $targetWeight = null;

    public ?float $targetRpe = null;

    public ?string $targetTempo = null;

    public ?int $restAfter = null;

    public ?int $targetDuration = null;

    public ?float $targetDistance = null;

    public ?int $targetPaceMin = null;

    public ?int $targetPaceMax = null;

    public ?int $targetHeartRateZone = null;

    public ?int $targetHeartRateMin = null;

    public ?int $targetHeartRateMax = null;

    public ?int $targetPower = null;

    public ?string $exerciseNotes = null;

    // Free-form muscle group toggles
    /** @var array<int, string> */
    public array $freeFormMuscleGroups = [];

    #[On('open-exercise-search')]
    public function openModal(int $sectionIndex, int $blockIndex): void
    {
        $this->targetSectionIndex = $sectionIndex;
        $this->targetBlockIndex = $blockIndex;
        $this->resetState();
        $this->showModal = true;
    }

    /**
     * @return Collection<int, Exercise>
     */
    #[Computed]
    public function exercises(): Collection
    {
        if ($this->query === '' && $this->muscleGroupFilter === null) {
            return Exercise::query()
                ->with('muscleGroups')
                ->limit(20)
                ->orderBy('name')
                ->get();
        }

        return Exercise::search($this->query)
            ->query(fn (Builder $builder) => $builder
                ->when($this->muscleGroupFilter, fn (Builder $q, string $filter): Builder => $q->whereHas(
                    'muscleGroups',
                    fn (Builder $mg): Builder => $mg->where('name', $filter),
                ))
                ->with('muscleGroups')
            )
            ->take(20)
            ->get();
    }

    /**
     * @return Collection<int, MuscleGroup>
     */
    #[Computed]
    public function muscleGroups(): Collection
    {
        return MuscleGroup::query()->orderBy('label')->get();
    }

    public function setMuscleGroupFilter(?string $name): void
    {
        $this->muscleGroupFilter = $this->muscleGroupFilter === $name ? null : $name;
        unset($this->exercises);
    }

    public function selectExercise(int $exerciseId): void
    {
        $exercise = Exercise::with('muscleGroups')->findOrFail($exerciseId);

        $this->selectedExerciseId = $exercise->id;
        $this->selectedName = $exercise->name;
        $this->selectedType = $this->inferExerciseType($exercise);
        $this->selectedMuscleGroups = $exercise->muscleGroups->pluck('label')->all();
        $this->step = 'configure';
    }

    public function goToFreeForm(): void
    {
        $this->selectedExerciseId = null;
        $this->selectedName = $this->query;
        $this->selectedType = 'strength';
        $this->freeFormMuscleGroups = [];
        $this->step = 'freeform';
    }

    public function toggleFreeFormMuscleGroup(string $label): void
    {
        if (in_array($label, $this->freeFormMuscleGroups, true)) {
            $this->freeFormMuscleGroups = array_values(
                array_filter($this->freeFormMuscleGroups, fn (string $l): bool => $l !== $label)
            );
        } else {
            $this->freeFormMuscleGroups[] = $label;
        }
    }

    public function confirmFreeForm(): void
    {
        $this->validate([
            'selectedName' => 'required|string|max:255',
            'selectedType' => 'required|in:strength,cardio,duration',
        ]);

        $this->selectedMuscleGroups = $this->freeFormMuscleGroups;
        $this->step = 'configure';
    }

    public function addExercise(): void
    {
        $this->dispatch('exercise-selected', [
            'sectionIndex' => $this->targetSectionIndex,
            'blockIndex' => $this->targetBlockIndex,
            'exerciseId' => $this->selectedExerciseId,
            'name' => $this->selectedName,
            'type' => $this->selectedType,
            'targetSets' => $this->targetSets,
            'targetRepsMin' => $this->targetRepsMin,
            'targetRepsMax' => $this->targetRepsMax,
            'targetWeight' => $this->targetWeight,
            'targetRpe' => $this->targetRpe,
            'targetTempo' => $this->targetTempo,
            'restAfter' => $this->restAfter,
            'targetDuration' => $this->targetDuration,
            'targetDistance' => $this->targetDistance,
            'targetPaceMin' => $this->targetPaceMin,
            'targetPaceMax' => $this->targetPaceMax,
            'targetHeartRateZone' => $this->targetHeartRateZone,
            'targetHeartRateMin' => $this->targetHeartRateMin,
            'targetHeartRateMax' => $this->targetHeartRateMax,
            'targetPower' => $this->targetPower,
            'exerciseNotes' => $this->exerciseNotes,
        ]);

        $this->showModal = false;
        $this->resetState();
    }

    public function backToSearch(): void
    {
        $this->resetConfigureState();
        $this->step = 'search';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetState();
    }

    public function render(): View
    {
        return view('livewire.workout.exercise-search');
    }

    private function inferExerciseType(Exercise $exercise): string
    {
        return match ($exercise->category) {
            'cardio' => 'cardio',
            'stretching' => 'duration',
            default => 'strength',
        };
    }

    private function resetState(): void
    {
        $this->query = '';
        $this->muscleGroupFilter = null;
        $this->step = 'search';
        $this->selectedExerciseId = null;
        $this->selectedName = '';
        $this->selectedType = 'strength';
        $this->selectedMuscleGroups = [];
        $this->freeFormMuscleGroups = [];
        $this->resetConfigureState();
        unset($this->exercises);
    }

    private function resetConfigureState(): void
    {
        $this->targetSets = null;
        $this->targetRepsMin = null;
        $this->targetRepsMax = null;
        $this->targetWeight = null;
        $this->targetRpe = null;
        $this->targetTempo = null;
        $this->restAfter = null;
        $this->targetDuration = null;
        $this->targetDistance = null;
        $this->targetPaceMin = null;
        $this->targetPaceMax = null;
        $this->targetHeartRateZone = null;
        $this->targetHeartRateMin = null;
        $this->targetHeartRateMax = null;
        $this->targetPower = null;
        $this->exerciseNotes = null;
    }
}
