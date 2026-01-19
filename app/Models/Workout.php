<?php

namespace App\Models;

use App\Enums\Workout\Sport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Sport $sport
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
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
            'sport' => Sport::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Step, $this>
     */
    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Step::class)->orderBy('sort_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Step, $this>
     */
    public function rootSteps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->steps()->whereNull('parent_step_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function canBeEdited(): bool
    {
        return ! $this->isCompleted();
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

        foreach ($this->rootSteps as $step) {
            $this->duplicateStep($step, $newWorkout);
        }

        return $newWorkout;
    }

    protected function duplicateStep(Step $step, Workout $newWorkout, ?int $parentId = null): void
    {
        $newStep = $step->replicate();
        $newStep->workout_id = $newWorkout->id;
        $newStep->parent_step_id = $parentId;
        $newStep->save();

        foreach ($step->children as $child) {
            $this->duplicateStep($child, $newWorkout, $newStep->id);
        }
    }

    public function totalDistanceInMeters(): int
    {
        return (int) $this->rootSteps->sum(function (Step $step) {
            return $this->calculateStepDistance($step);
        });
    }

    public function estimatedTotalDistanceInMeters(): int
    {
        return app(\App\Services\Workout\WorkoutEstimator::class)->estimateDistance($this);
    }

    protected function calculateStepDistance(Step $step): int
    {
        if ($step->step_kind === \App\Enums\Workout\StepKind::Repeat) {
            $childDistance = $step->children->sum(function (Step $child) {
                return $this->calculateStepDistance($child);
            });

            return (int) ($childDistance * $step->repeat_count);
        }

        if ($step->duration_type === \App\Enums\Workout\DurationType::Distance) {
            return $step->duration_value ?? 0;
        }

        return 0;
    }

    public function totalDurationInSeconds(): int
    {
        return (int) $this->rootSteps->sum(function (Step $step) {
            return $this->calculateStepDuration($step);
        });
    }

    public function estimatedTotalDurationInSeconds(): int
    {
        return app(\App\Services\Workout\WorkoutEstimator::class)->estimateDuration($this);
    }

    protected function calculateStepDuration(Step $step): int
    {
        if ($step->step_kind === \App\Enums\Workout\StepKind::Repeat) {
            $childDuration = $step->children->sum(function (Step $child) {
                return $this->calculateStepDuration($child);
            });

            return (int) ($childDuration * $step->repeat_count);
        }

        if ($step->duration_type === \App\Enums\Workout\DurationType::Time) {
            return $step->duration_value ?? 0;
        }

        return 0;
    }
}
