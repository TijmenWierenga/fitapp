<?php

namespace App\Models;

use App\Enums\WorkoutType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property WorkoutType $type
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkoutStep> $steps
 */
class Workout extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'scheduled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => WorkoutType::class,
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
     * @return HasMany<WorkoutStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkoutStep::class)->whereNull('parent_id')->orderBy('order');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<$this>  $query
     */
    public function scopeUpcoming(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('completed_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<$this>  $query
     */
    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<$this>  $query
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
            'type' => $this->type,
            'scheduled_at' => $scheduledAt,
        ]);

        foreach ($this->steps as $step) {
            $this->duplicateStep($step, $newWorkout->id);
        }

        return $newWorkout;
    }

    protected function duplicateStep(WorkoutStep $step, int $workoutId, ?int $parentId = null): void
    {
        $newStep = WorkoutStep::create([
            'workout_id' => $workoutId,
            'parent_id' => $parentId,
            'order' => $step->order,
            'type' => $step->type,
            'intensity' => $step->intensity,
            'duration_type' => $step->duration_type,
            'duration_value' => $step->duration_value,
            'target_type' => $step->target_type,
            'target_value_low' => $step->target_value_low,
            'target_value_high' => $step->target_value_high,
            'notes' => $step->notes,
        ]);

        foreach ($step->children as $childStep) {
            $this->duplicateStep($childStep, $workoutId, $newStep->id);
        }
    }
}
