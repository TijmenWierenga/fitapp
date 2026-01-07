<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $workout_id
 * @property int|null $parent_step_id
 * @property int $sort_order
 * @property string $step_kind
 * @property string $intensity
 * @property string|null $name
 * @property string|null $notes
 * @property string|null $duration_type
 * @property int|null $duration_value
 * @property string|null $target_type
 * @property string|null $target_mode
 * @property int|null $target_zone
 * @property int|null $target_low
 * @property int|null $target_high
 * @property int|null $repeat_count
 * @property bool $skip_last_recovery
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Workout $workout
 * @property-read WorkoutStep|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<WorkoutStep> $children
 */
class WorkoutStep extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutStepFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'parent_step_id',
        'sort_order',
        'step_kind',
        'intensity',
        'name',
        'notes',
        'duration_type',
        'duration_value',
        'target_type',
        'target_mode',
        'target_zone',
        'target_low',
        'target_high',
        'repeat_count',
        'skip_last_recovery',
    ];

    protected function casts(): array
    {
        return [
            'skip_last_recovery' => 'boolean',
            'sort_order' => 'integer',
            'duration_value' => 'integer',
            'target_zone' => 'integer',
            'target_low' => 'integer',
            'target_high' => 'integer',
            'repeat_count' => 'integer',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Workout, $this>
     */
    public function workout(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<WorkoutStep, $this>
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkoutStep::class, 'parent_step_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutStep, $this>
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutStep::class, 'parent_step_id')->orderBy('sort_order');
    }

    public function isRepeat(): bool
    {
        return $this->step_kind === 'repeat';
    }

    public function isNormalStep(): bool
    {
        return !$this->isRepeat();
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getDepth(): int
    {
        return $this->parent_step_id === null ? 0 : 1;
    }

    /**
     * Get the nesting depth from the root
     */
    public function getNestingDepth(): int
    {
        $depth = 0;
        $current = $this;
        
        while ($current->parent_step_id !== null) {
            $depth++;
            $current = $current->parent;
        }
        
        return $depth;
    }

    /**
     * Validate step according to domain rules
     */
    public function validate(): array
    {
        $errors = [];

        // Normal steps cannot have children
        if ($this->isNormalStep() && $this->hasChildren()) {
            $errors[] = 'Normal steps cannot have children';
        }

        // Repeat steps must have at least 1 child
        if ($this->isRepeat() && !$this->hasChildren()) {
            $errors[] = 'Repeat steps must have at least 1 child';
        }

        // Max nesting depth: 2 (workout → repeat → normal)
        if ($this->getNestingDepth() > 1) {
            $errors[] = 'Maximum nesting depth is 2';
        }

        // Repeat cannot be nested inside repeat
        if ($this->isRepeat() && $this->parent !== null && $this->parent->isRepeat()) {
            $errors[] = 'Repeat cannot be nested inside another repeat';
        }

        // Duration validation for normal steps
        if ($this->isNormalStep()) {
            if ($this->duration_type === 'time') {
                if ($this->duration_value < 10 || $this->duration_value > 21600) {
                    $errors[] = 'Time duration must be between 10 seconds and 6 hours';
                }
            } elseif ($this->duration_type === 'distance') {
                if ($this->duration_value < 10 || $this->duration_value > 100000) {
                    $errors[] = 'Distance must be between 10 meters and 100 km';
                }
                if ($this->duration_value % 10 !== 0) {
                    $errors[] = 'Distance must be divisible by 10';
                }
            } elseif ($this->duration_type === 'lap_press') {
                if ($this->duration_value !== null) {
                    $errors[] = 'Lap press duration must have no value';
                }
            }
        }

        // Target validation
        if ($this->target_type === 'none' || $this->target_type === null) {
            if ($this->target_mode !== null || $this->target_zone !== null || 
                $this->target_low !== null || $this->target_high !== null) {
                $errors[] = 'When target_type is none, all target fields must be null';
            }
        } elseif ($this->target_type === 'heart_rate') {
            if ($this->target_mode === 'zone' && ($this->target_zone < 1 || $this->target_zone > 5)) {
                $errors[] = 'HR zone must be between 1 and 5';
            }
            if ($this->target_mode === 'range') {
                if ($this->target_low < 40 || $this->target_low > 230 || 
                    $this->target_high < 40 || $this->target_high > 230) {
                    $errors[] = 'HR range must be between 40 and 230 bpm';
                }
                if ($this->target_low >= $this->target_high) {
                    $errors[] = 'HR low must be less than high';
                }
            }
        } elseif ($this->target_type === 'pace') {
            if ($this->target_mode === 'zone' && ($this->target_zone < 1 || $this->target_zone > 5)) {
                $errors[] = 'Pace zone must be between 1 and 5';
            }
            if ($this->target_mode === 'range') {
                if ($this->target_low < 120 || $this->target_low > 900 || 
                    $this->target_high < 120 || $this->target_high > 900) {
                    $errors[] = 'Pace range must be between 120 and 900 seconds/km';
                }
                if ($this->target_low >= $this->target_high) {
                    $errors[] = 'Pace low must be less than high';
                }
            }
        }

        // Repeat validation
        if ($this->isRepeat()) {
            if ($this->repeat_count < 2) {
                $errors[] = 'Repeat count must be at least 2';
            }
        }

        return $errors;
    }
}
