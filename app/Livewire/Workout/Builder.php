<?php

namespace App\Livewire\Workout;

use App\Enums\Workout\Activity;
use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use App\Models\Workout;
use App\Support\Workout\DistanceConverter;
use App\Support\Workout\PaceConverter;
use App\Support\Workout\TimeConverter;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Builder extends Component
{
    public ?Workout $workout = null;

    public string $name = '';

    public ?string $notes = null;

    public Activity $activity = Activity::Run;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public array $steps = [];

    public bool $showingActivityTypeChangeModal = false;

    public ?Activity $pendingActivity = null;

    // Modal state
    public bool $showingStepModal = false;

    public array $editingStepData = [];

    public ?string $editingStepPath = null; // e.g. "0" or "1.children.0"

    public function mount(?Workout $workout = null): void
    {
        if ($workout && $workout->exists) {
            if (! $workout->canBeEdited()) {
                abort(403, 'Completed workouts cannot be edited.');
            }

            $this->workout = $workout;
            $this->name = $workout->name;
            $this->notes = $workout->notes;
            $this->activity = $workout->activity;
            $this->scheduled_date = $workout->scheduled_at->format('Y-m-d');
            $this->scheduled_time = $workout->scheduled_at->format('H:i');
            $this->loadSteps();
        } else {
            $this->scheduled_date = now()->format('Y-m-d');
            $this->scheduled_time = now()->format('H:i');
            // Default first step for running activity
            $this->addStep();
        }
    }

    protected function loadSteps(): void
    {
        $this->steps = $this->workout->rootSteps()
            ->with('children')
            ->get()
            ->map(fn ($step) => $this->mapStepToArray($step))
            ->toArray();
    }

    protected function mapStepToArray($step): array
    {
        $data = $step->toArray();
        if ($step->step_kind === StepKind::Repeat) {
            $data['children'] = $step->children->map(fn ($child) => $this->mapStepToArray($child))->toArray();
        }

        return $data;
    }

    public function addStep(?int $repeatIndex = null): void
    {
        $newStep = [
            'step_kind' => StepKind::Run->value,
            'intensity' => Intensity::Active->value,
            'duration_type' => DurationType::Distance->value,
            'duration_value' => 1000,
            'target_type' => TargetType::None->value,
            'target_mode' => null,
            'target_zone' => null,
            'target_low' => null,
            'target_high' => null,
            'name' => null,
            'notes' => null,
        ];

        if ($repeatIndex !== null) {
            $this->steps[$repeatIndex]['children'][] = $newStep;
        } else {
            $this->steps[] = $newStep;
        }
    }

    public function addRepeat(): void
    {
        $this->steps[] = [
            'step_kind' => StepKind::Repeat->value,
            'repeat_count' => 2,
            'skip_last_recovery' => false,
            'children' => [
                [
                    'step_kind' => StepKind::Run->value,
                    'intensity' => Intensity::Active->value,
                    'duration_type' => DurationType::Distance->value,
                    'duration_value' => 1000,
                    'target_type' => TargetType::None->value,
                    'target_mode' => null,
                    'target_zone' => null,
                    'target_low' => null,
                    'target_high' => null,
                    'name' => null,
                    'notes' => null,
                ],
                [
                    'step_kind' => StepKind::Recovery->value,
                    'intensity' => Intensity::Rest->value,
                    'duration_type' => DurationType::Time->value,
                    'duration_value' => 60,
                    'target_type' => TargetType::None->value,
                    'target_mode' => null,
                    'target_zone' => null,
                    'target_low' => null,
                    'target_high' => null,
                    'name' => null,
                    'notes' => null,
                ],
            ],
        ];
    }

    public function removeStep(string $path): void
    {
        if (! str_contains($path, '.')) {
            unset($this->steps[$path]);
            $this->steps = array_values($this->steps);
        } else {
            [$repeatIndex, $childKey, $stepIndex] = explode('.', $path);
            unset($this->steps[$repeatIndex][$childKey][$stepIndex]);
            $this->steps[$repeatIndex][$childKey] = array_values($this->steps[$repeatIndex][$childKey]);
        }
    }

    public function editStep(string $path): void
    {
        $this->editingStepPath = $path;
        $step = data_get($this->steps, $path);

        $this->editingStepData = $step;

        // Initialize human-readable fields for the form
        if ($step['step_kind'] !== StepKind::Repeat->value) {
            if ($step['duration_type'] === DurationType::Time->value) {
                $totalSeconds = $step['duration_value'] ?? 0;
                $this->editingStepData['duration_minutes'] = (int) floor($totalSeconds / 60);
                $this->editingStepData['duration_seconds'] = $totalSeconds % 60;
            } elseif ($step['duration_type'] === DurationType::Distance->value) {
                $this->editingStepData['duration_km'] = ($step['duration_value'] ?? 0) / 1000;
            }

            if ($step['target_type'] === TargetType::Pace->value && $step['target_mode'] === TargetMode::Range->value) {
                $low = PaceConverter::fromSecondsPerKm($step['target_low'] ?? 0);
                $high = PaceConverter::fromSecondsPerKm($step['target_high'] ?? 0);
                $this->editingStepData['target_low_min'] = $low['minutes'];
                $this->editingStepData['target_low_sec'] = $low['seconds'];
                $this->editingStepData['target_high_min'] = $high['minutes'];
                $this->editingStepData['target_high_sec'] = $high['seconds'];
            }
        }

        $this->showingStepModal = true;
    }

    public function updatedEditingStepDataStepKind($value): void
    {
        $this->editingStepData['intensity'] = match ($value) {
            StepKind::Warmup->value => Intensity::Warmup->value,
            StepKind::Run->value => Intensity::Active->value,
            StepKind::Recovery->value => Intensity::Rest->value,
            StepKind::Cooldown->value => Intensity::Cooldown->value,
            default => $this->editingStepData['intensity'] ?? Intensity::Active->value,
        };
    }

    public function saveStep(): void
    {
        // Convert human-readable back to normalized
        if ($this->editingStepData['step_kind'] !== StepKind::Repeat->value) {
            if ($this->editingStepData['duration_type'] === DurationType::Time->value) {
                $this->editingStepData['duration_value'] = TimeConverter::toSeconds(
                    (int) ($this->editingStepData['duration_minutes'] ?? 0),
                    (int) ($this->editingStepData['duration_seconds'] ?? 0)
                );
            } elseif ($this->editingStepData['duration_type'] === DurationType::Distance->value) {
                $this->editingStepData['duration_value'] = DistanceConverter::toMeters(
                    (int) ($this->editingStepData['duration_km'] ?? 0),
                    (int) ($this->editingStepData['duration_tens'] ?? 0)
                );
            }

            if ($this->editingStepData['target_type'] === TargetType::Pace->value && $this->editingStepData['target_mode'] === TargetMode::Range->value) {
                $this->editingStepData['target_low'] = PaceConverter::toSecondsPerKm(
                    (int) ($this->editingStepData['target_low_min'] ?? 0),
                    (int) ($this->editingStepData['target_low_sec'] ?? 0)
                );
                $this->editingStepData['target_high'] = PaceConverter::toSecondsPerKm(
                    (int) ($this->editingStepData['target_high_min'] ?? 0),
                    (int) ($this->editingStepData['target_high_sec'] ?? 0)
                );
            }

            if ($this->editingStepData['target_type'] === TargetType::None->value) {
                $this->editingStepData['target_mode'] = null;
                $this->editingStepData['target_zone'] = null;
                $this->editingStepData['target_low'] = null;
                $this->editingStepData['target_high'] = null;
            }
        }

        data_set($this->steps, $this->editingStepPath, $this->editingStepData);
        $this->showingStepModal = false;
    }

    public function moveUp(string $path): void
    {
        if (! str_contains($path, '.')) {
            $index = (int) $path;
            if ($index > 0) {
                $temp = $this->steps[$index - 1];
                $this->steps[$index - 1] = $this->steps[$index];
                $this->steps[$index] = $temp;
            }
        } else {
            [$repeatIndex, $childKey, $stepIndex] = explode('.', $path);
            $stepIndex = (int) $stepIndex;
            if ($stepIndex > 0) {
                $temp = $this->steps[$repeatIndex][$childKey][$stepIndex - 1];
                $this->steps[$repeatIndex][$childKey][$stepIndex - 1] = $this->steps[$repeatIndex][$childKey][$stepIndex];
                $this->steps[$repeatIndex][$childKey][$stepIndex] = $temp;
            }
        }
    }

    public function moveDown(string $path): void
    {
        if (! str_contains($path, '.')) {
            $index = (int) $path;
            if ($index < count($this->steps) - 1) {
                $temp = $this->steps[$index + 1];
                $this->steps[$index + 1] = $this->steps[$index];
                $this->steps[$index] = $temp;
            }
        } else {
            [$repeatIndex, $childKey, $stepIndex] = explode('.', $path);
            $stepIndex = (int) $stepIndex;
            if ($stepIndex < count($this->steps[$repeatIndex][$childKey]) - 1) {
                $temp = $this->steps[$repeatIndex][$childKey][$stepIndex + 1];
                $this->steps[$repeatIndex][$childKey][$stepIndex + 1] = $this->steps[$repeatIndex][$childKey][$stepIndex];
                $this->steps[$repeatIndex][$childKey][$stepIndex] = $temp;
            }
        }
    }

    public function selectActivity(string $activityValue): void
    {
        $activity = Activity::from($activityValue);

        // If changing FROM running to another type and there are steps, show confirmation
        if ($this->activity->hasSteps() && ! $activity->hasSteps() && count($this->steps) > 0) {
            $this->pendingActivity = $activity;
            $this->showingActivityTypeChangeModal = true;

            return;
        }

        $this->applyActivityChange($activity);
    }

    public function confirmActivityChange(): void
    {
        $this->applyActivityChange($this->pendingActivity);
        $this->showingActivityTypeChangeModal = false;
        $this->pendingActivity = null;
    }

    public function cancelActivityChange(): void
    {
        $this->showingActivityTypeChangeModal = false;
        $this->pendingActivity = null;
        // Force re-render to reset the select value
        $this->dispatch('$refresh');
    }

    protected function applyActivityChange(Activity $activity): void
    {
        $oldActivity = $this->activity;
        $this->activity = $activity;

        // Clear steps when changing FROM running to another type
        if ($oldActivity->hasSteps() && ! $activity->hasSteps()) {
            $this->steps = [];
        }

        // Add default step when changing TO running if there are no steps
        if ($activity->hasSteps() && count($this->steps) === 0) {
            $this->addStep();
        }
    }

    public function saveWorkout(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
        ];

        if ($this->activity->hasSteps()) {
            $rules['steps'] = 'required|array|min:1';
        }

        $this->validate($rules);

        $scheduledAt = "{$this->scheduled_date} {$this->scheduled_time}";

        if ($this->workout && $this->workout->exists && ! $this->workout->fresh()->canBeEdited()) {
            abort(403, 'Completed workouts cannot be edited.');
        }

        if (! $this->workout) {
            $this->workout = new Workout;
            $this->workout->user_id = auth()->id();
        }

        $this->workout->name = $this->name;
        $this->workout->notes = $this->notes;
        $this->workout->activity = $this->activity;
        $this->workout->scheduled_at = $scheduledAt;
        $this->workout->save();

        if ($this->activity->hasSteps()) {
            $this->syncSteps();
        } else {
            // Delete any existing steps when changing to a non-step-builder activity
            $this->workout->steps()->delete();
        }

        $this->redirect(route('dashboard'));
    }

    protected function syncSteps(): void
    {
        $this->workout->steps()->delete();

        $order = 0;
        foreach ($this->steps as $stepData) {
            $this->createStep($stepData, $order++);
        }
    }

    protected function createStep(array $data, int $order, ?int $parentId = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children'], $data['id'], $data['workout_id'], $data['parent_step_id'], $data['created_at'], $data['updated_at']);

        // Remove human-readable helper fields if they exist
        unset(
            $data['duration_minutes'],
            $data['duration_seconds'],
            $data['duration_km'],
            $data['duration_tens'],
            $data['target_low_min'],
            $data['target_low_sec'],
            $data['target_high_min'],
            $data['target_high_sec']
        );

        $step = $this->workout->steps()->create(array_merge($data, [
            'sort_order' => $order,
            'parent_step_id' => $parentId,
        ]));

        if (! empty($children)) {
            $childOrder = 0;
            foreach ($children as $childData) {
                $this->createStep($childData, $childOrder++, $step->id);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.workout.builder');
    }
}
