<?php

namespace App\Livewire\Workout;

use App\Enums\WorkoutType;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $type = 'running';

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public array $steps = [];

    public function mount(): void
    {
        $this->type = WorkoutType::Running->value;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required'],
        ]);

        $scheduledAt = str($this->scheduled_date)->append(' ', $this->scheduled_time)->toString();

        $workout = auth()->user()->workouts()->create([
            'name' => $this->name,
            'type' => $this->type,
            'scheduled_at' => $scheduledAt,
        ]);

        foreach ($this->steps as $index => $stepData) {
            $this->saveStep($workout, $stepData, $index);
        }

        $this->redirect('/dashboard');
    }

    protected function saveStep($workout, $stepData, $order, $parentId = null): void
    {
        $step = $workout->steps()->create([
            'parent_id' => $parentId,
            'order' => $order,
            'type' => $stepData['type'],
            'intensity' => $stepData['intensity'] ?? null,
            'duration_type' => $stepData['duration_type'],
            'duration_value' => $stepData['duration_value'] ?? null,
            'target_type' => $stepData['target_type'] ?? null,
            'target_value_low' => $stepData['target_value_low'] ?? null,
            'target_value_high' => $stepData['target_value_high'] ?? null,
            'notes' => $stepData['notes'] ?? null,
        ]);

        if (! empty($stepData['children'])) {
            foreach ($stepData['children'] as $childIndex => $childData) {
                $this->saveStep($workout, $childData, $childIndex, $step->id);
            }
        }
    }

    public function addStep(): void
    {
        $this->steps[] = [
            'type' => 'step',
            'intensity' => 'active',
            'duration_type' => 'time',
            'duration_value' => '600',
            'target_type' => 'open',
            'children' => [],
        ];
    }

    public function addRepeat(): void
    {
        $this->steps[] = [
            'type' => 'repetition',
            'duration_type' => 'repetition_count',
            'duration_value' => '2',
            'children' => [
                [
                    'type' => 'step',
                    'intensity' => 'active',
                    'duration_type' => 'time',
                    'duration_value' => '60',
                    'target_type' => 'open',
                ],
                [
                    'type' => 'step',
                    'intensity' => 'rest',
                    'duration_type' => 'time',
                    'duration_value' => '60',
                    'target_type' => 'open',
                ],
            ],
        ];
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function addChildStep(int $parentIndex): void
    {
        $this->steps[$parentIndex]['children'][] = [
            'type' => 'step',
            'intensity' => 'active',
            'duration_type' => 'time',
            'duration_value' => '60',
            'target_type' => 'open',
        ];
    }

    public function removeChildStep(int $parentIndex, int $childIndex): void
    {
        unset($this->steps[$parentIndex]['children'][$childIndex]);
        $this->steps[$parentIndex]['children'] = array_values($this->steps[$parentIndex]['children']);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.workout.create');
    }
}
