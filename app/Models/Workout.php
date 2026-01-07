<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $sport
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<WorkoutStep> $steps
 */
class Workout extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'sport',
        'scheduled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutStep, $this>
     */
    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutStep::class)->whereNull('parent_step_id')->orderBy('sort_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutStep, $this>
     */
    public function allSteps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutStep::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     */
    public function scopeUpcoming(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('completed_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     */
    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     */
    public function scopeOverdue(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('completed_at')
            ->where('scheduled_at', '<', now())
            ->orderBy('scheduled_at', 'desc');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    public function canBeDeleted(): bool
    {
        // Cannot delete completed workouts
        if ($this->isCompleted()) {
            return false;
        }

        // Cannot delete past workouts (except today)
        if ($this->scheduled_at->isPast() && ! $this->scheduled_at->isToday()) {
            return false;
        }

        return true;
    }

    public function deleteIfAllowed(): bool
    {
        if (! $this->canBeDeleted()) {
            return false;
        }

        $this->delete();

        return true;
    }

    public function duplicate(\DateTimeInterface $scheduledAt): self
    {
        $newWorkout = self::create([
            'user_id' => $this->user_id,
            'name' => $this->name,
            'sport' => $this->sport,
            'scheduled_at' => $scheduledAt,
        ]);

        // Duplicate all steps
        foreach ($this->steps as $step) {
            $this->duplicateStep($step, $newWorkout->id, null);
        }

        return $newWorkout;
    }

    private function duplicateStep(WorkoutStep $step, int $workoutId, ?int $parentStepId): void
    {
        $newStep = WorkoutStep::create([
            'workout_id' => $workoutId,
            'parent_step_id' => $parentStepId,
            'sort_order' => $step->sort_order,
            'step_kind' => $step->step_kind,
            'intensity' => $step->intensity,
            'name' => $step->name,
            'notes' => $step->notes,
            'duration_type' => $step->duration_type,
            'duration_value' => $step->duration_value,
            'target_type' => $step->target_type,
            'target_mode' => $step->target_mode,
            'target_zone' => $step->target_zone,
            'target_low' => $step->target_low,
            'target_high' => $step->target_high,
            'repeat_count' => $step->repeat_count,
            'skip_last_recovery' => $step->skip_last_recovery,
        ]);

        // Recursively duplicate children
        foreach ($step->children as $child) {
            $this->duplicateStep($child, $workoutId, $newStep->id);
        }
    }

    /**
     * Validate workout according to domain rules
     */
    public function validateWorkout(): array
    {
        $errors = [];

        // Workout must have at least 1 step
        if ($this->steps()->count() === 0) {
            $errors[] = 'Workout must have at least 1 step';
        }

        return $errors;
    }
}
