<?php

namespace App\Livewire\Workout;

use App\Models\Workout;
use App\Models\WorkoutStep;
use Livewire\Component;

class Builder extends Component
{
    public ?Workout $workout = null;
    
    public string $name = '';
    public string $scheduled_date = '';
    public string $scheduled_time = '';
    
    public bool $showStepModal = false;
    public ?int $editingStepId = null;
    public ?int $parentStepId = null;
    
    // Step form fields
    public string $step_kind = 'run';
    public string $intensity = 'active';
    public ?string $step_name = null;
    public ?string $notes = null;
    
    // Duration fields
    public string $duration_type = 'distance';
    public int $duration_minutes = 0;
    public int $duration_seconds = 0;
    public int $duration_km = 1;
    public int $duration_tens_of_meters = 0;
    
    // Target fields
    public string $target_type = 'none';
    public ?string $target_mode = null;
    public ?int $target_zone = null;
    public int $target_low_minutes = 0;
    public int $target_low_seconds = 0;
    public int $target_high_minutes = 0;
    public int $target_high_seconds = 0;
    public ?int $target_low_bpm = null;
    public ?int $target_high_bpm = null;
    
    // Repeat fields
    public int $repeat_count = 2;
    public bool $skip_last_recovery = false;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->workout = auth()->user()->workouts()->findOrFail($id);
            $this->name = $this->workout->name;
            $this->scheduled_date = $this->workout->scheduled_at->format('Y-m-d');
            $this->scheduled_time = $this->workout->scheduled_at->format('H:i');
        } else {
            // Set defaults for new workout
            $this->scheduled_date = now()->addDay()->format('Y-m-d');
            $this->scheduled_time = '08:00';
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required'],
        ]);

        $scheduledAt = $this->scheduled_date.' '.$this->scheduled_time;

        if ($this->workout) {
            $this->workout->update([
                'name' => $this->name,
                'sport' => 'running',
                'scheduled_at' => $scheduledAt,
            ]);
        } else {
            $this->workout = auth()->user()->workouts()->create([
                'name' => $this->name,
                'sport' => 'running',
                'scheduled_at' => $scheduledAt,
            ]);
        }

        session()->flash('message', 'Workout saved successfully.');
        $this->redirect('/dashboard');
    }

    public function openStepModal(?int $parentStepId = null, ?int $stepId = null): void
    {
        $this->parentStepId = $parentStepId;
        $this->editingStepId = $stepId;
        
        if ($stepId) {
            $this->loadStepForEditing($stepId);
        } else {
            $this->resetStepForm();
        }
        
        $this->showStepModal = true;
    }

    public function closeStepModal(): void
    {
        $this->showStepModal = false;
        $this->editingStepId = null;
        $this->parentStepId = null;
        $this->resetStepForm();
    }

    private function resetStepForm(): void
    {
        $this->step_kind = $this->parentStepId ? 'run' : 'run';
        $this->intensity = 'active';
        $this->step_name = null;
        $this->notes = null;
        $this->duration_type = 'distance';
        $this->duration_minutes = 0;
        $this->duration_seconds = 0;
        $this->duration_km = 1;
        $this->duration_tens_of_meters = 0;
        $this->target_type = 'none';
        $this->target_mode = null;
        $this->target_zone = null;
        $this->target_low_minutes = 4;
        $this->target_low_seconds = 30;
        $this->target_high_minutes = 5;
        $this->target_high_seconds = 0;
        $this->target_low_bpm = 120;
        $this->target_high_bpm = 150;
        $this->repeat_count = 2;
        $this->skip_last_recovery = false;
    }

    private function loadStepForEditing(int $stepId): void
    {
        $step = WorkoutStep::findOrFail($stepId);
        
        $this->step_kind = $step->step_kind;
        $this->intensity = $step->intensity;
        $this->step_name = $step->name;
        $this->notes = $step->notes;
        
        if ($step->isRepeat()) {
            $this->repeat_count = $step->repeat_count ?? 2;
            $this->skip_last_recovery = $step->skip_last_recovery;
        } else {
            $this->duration_type = $step->duration_type ?? 'distance';
            
            if ($step->duration_type === 'time' && $step->duration_value) {
                $time = \App\ValueObjects\TimeValue::fromSeconds($step->duration_value);
                $this->duration_minutes = $time->minutes;
                $this->duration_seconds = $time->seconds;
            } elseif ($step->duration_type === 'distance' && $step->duration_value) {
                $distance = \App\ValueObjects\DistanceValue::fromMeters($step->duration_value);
                $this->duration_km = $distance->kilometers;
                $this->duration_tens_of_meters = $distance->tensOfMeters;
            }
            
            $this->target_type = $step->target_type ?? 'none';
            $this->target_mode = $step->target_mode;
            $this->target_zone = $step->target_zone;
            
            if ($step->target_type === 'pace') {
                if ($step->target_low) {
                    $lowPace = \App\ValueObjects\PaceValue::fromSecondsPerKm($step->target_low);
                    $this->target_low_minutes = $lowPace->minutes;
                    $this->target_low_seconds = $lowPace->seconds;
                }
                if ($step->target_high) {
                    $highPace = \App\ValueObjects\PaceValue::fromSecondsPerKm($step->target_high);
                    $this->target_high_minutes = $highPace->minutes;
                    $this->target_high_seconds = $highPace->seconds;
                }
            } elseif ($step->target_type === 'heart_rate') {
                $this->target_low_bpm = $step->target_low;
                $this->target_high_bpm = $step->target_high;
            }
        }
    }

    public function saveStep(): void
    {
        if (!$this->workout) {
            $this->addError('workout', 'Please save the workout first before adding steps.');
            return;
        }

        $data = [
            'workout_id' => $this->workout->id,
            'parent_step_id' => $this->parentStepId,
            'step_kind' => $this->step_kind,
            'intensity' => $this->getIntensityForStepKind(),
            'name' => $this->step_name,
            'notes' => $this->notes,
        ];

        if ($this->step_kind === 'repeat') {
            $data['repeat_count'] = $this->repeat_count;
            $data['skip_last_recovery'] = $this->skip_last_recovery;
            $data['duration_type'] = null;
            $data['duration_value'] = null;
            $data['target_type'] = null;
            $data['target_mode'] = null;
            $data['target_zone'] = null;
            $data['target_low'] = null;
            $data['target_high'] = null;
        } else {
            $data['duration_type'] = $this->duration_type;
            
            if ($this->duration_type === 'time') {
                $data['duration_value'] = ($this->duration_minutes * 60) + $this->duration_seconds;
            } elseif ($this->duration_type === 'distance') {
                $data['duration_value'] = ($this->duration_km * 1000) + ($this->duration_tens_of_meters * 10);
            } else {
                $data['duration_value'] = null;
            }
            
            $data['target_type'] = $this->target_type;
            
            if ($this->target_type === 'none') {
                $data['target_mode'] = null;
                $data['target_zone'] = null;
                $data['target_low'] = null;
                $data['target_high'] = null;
            } elseif ($this->target_mode === 'zone') {
                $data['target_mode'] = 'zone';
                $data['target_zone'] = $this->target_zone;
                $data['target_low'] = null;
                $data['target_high'] = null;
            } else {
                $data['target_mode'] = 'range';
                $data['target_zone'] = null;
                
                if ($this->target_type === 'pace') {
                    $data['target_low'] = ($this->target_low_minutes * 60) + $this->target_low_seconds;
                    $data['target_high'] = ($this->target_high_minutes * 60) + $this->target_high_seconds;
                } else {
                    $data['target_low'] = $this->target_low_bpm;
                    $data['target_high'] = $this->target_high_bpm;
                }
            }
            
            $data['repeat_count'] = null;
            $data['skip_last_recovery'] = false;
        }

        // Get the next sort order
        if ($this->parentStepId) {
            $maxSortOrder = WorkoutStep::where('parent_step_id', $this->parentStepId)->max('sort_order') ?? -1;
        } else {
            $maxSortOrder = $this->workout->steps()->max('sort_order') ?? -1;
        }
        $data['sort_order'] = $maxSortOrder + 1;

        if ($this->editingStepId) {
            $step = WorkoutStep::findOrFail($this->editingStepId);
            $step->update($data);
        } else {
            WorkoutStep::create($data);
        }

        $this->closeStepModal();
    }

    private function getIntensityForStepKind(): string
    {
        return match($this->step_kind) {
            'warmup' => 'warmup',
            'cooldown' => 'cooldown',
            'recovery' => 'rest',
            'run' => 'active',
            default => $this->intensity,
        };
    }

    public function deleteStep(int $stepId): void
    {
        $step = WorkoutStep::findOrFail($stepId);
        $step->delete();
    }

    public function addDefaultRepeat(): void
    {
        if (!$this->workout) {
            $this->addError('workout', 'Please save the workout first before adding steps.');
            return;
        }

        $maxSortOrder = $this->workout->steps()->max('sort_order') ?? -1;
        
        $repeat = WorkoutStep::create([
            'workout_id' => $this->workout->id,
            'parent_step_id' => null,
            'sort_order' => $maxSortOrder + 1,
            'step_kind' => 'repeat',
            'intensity' => 'active',
            'repeat_count' => 2,
            'skip_last_recovery' => false,
        ]);

        // Add default run step
        WorkoutStep::create([
            'workout_id' => $this->workout->id,
            'parent_step_id' => $repeat->id,
            'sort_order' => 0,
            'step_kind' => 'run',
            'intensity' => 'active',
            'duration_type' => 'distance',
            'duration_value' => 1000,
            'target_type' => 'none',
        ]);

        // Add default recovery step
        WorkoutStep::create([
            'workout_id' => $this->workout->id,
            'parent_step_id' => $repeat->id,
            'sort_order' => 1,
            'step_kind' => 'recovery',
            'intensity' => 'rest',
            'duration_type' => 'time',
            'duration_value' => 60,
            'target_type' => 'none',
        ]);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $steps = $this->workout ? $this->workout->steps()->with('children')->get() : collect();
        
        return view('livewire.workout.builder', [
            'steps' => $steps,
        ]);
    }
}
