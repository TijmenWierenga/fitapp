<?php

namespace App\Models;

use App\Enums\Workout\Activity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'activity',
        'notes',
        'scheduled_at',
        'completed_at',
        'rpe',
        'feeling',
    ];

    protected $casts = [
        'activity' => Activity::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'rpe' => 'integer',
        'feeling' => 'integer',
    ];

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function notes(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $trimmed = trim($value);

                return $trimmed === '' ? null : $trimmed;
            },
        );
    }

    /**
     * Root-level blocks ordered by position.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutBlock, $this>
     */
    public function blocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutBlock::class)
            ->whereNull('parent_id')
            ->orderBy('position');
    }

    /**
     * All blocks for this workout (flat).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutBlock, $this>
     */
    public function allBlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutBlock::class);
    }

    /**
     * Root blocks eager-loaded with nested children and blockable content.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutBlock, $this>
     */
    public function blockTree(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->blocks()->with([
            'nestedChildren',
            'blockable' => fn (\Illuminate\Database\Eloquent\Relations\MorphTo $morphTo) => $morphTo->morphWith([
                ExerciseGroup::class => ['entries.exercise'],
            ]),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WorkoutMuscleLoadSnapshot, $this>
     */
    public function muscleLoadSnapshots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutMuscleLoadSnapshot::class);
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

    public function markAsCompleted(int $rpe, int $feeling): void
    {
        $this->update([
            'completed_at' => now(),
            'rpe' => $rpe,
            'feeling' => $feeling,
        ]);
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
            'activity' => $this->activity,
            'notes' => $this->notes,
            'scheduled_at' => $scheduledAt,
        ]);

        foreach ($this->blocks as $block) {
            $this->duplicateBlock($block, $newWorkout);
        }

        return $newWorkout;
    }

    protected function duplicateBlock(WorkoutBlock $block, self $newWorkout, ?int $parentId = null): void
    {
        $blockable = $block->blockable;
        $newBlockableId = null;
        $newBlockableType = $block->blockable_type;

        if ($blockable) {
            $newBlockable = $blockable->replicate();
            $newBlockable->save();
            $newBlockableId = $newBlockable->id;

            if ($blockable instanceof ExerciseGroup) {
                foreach ($blockable->entries as $entry) {
                    $newEntry = $entry->replicate();
                    $newEntry->exercise_group_id = $newBlockable->id;
                    $newEntry->save();
                }
            }
        }

        $newBlock = $block->replicate();
        $newBlock->workout_id = $newWorkout->id;
        $newBlock->parent_id = $parentId;
        $newBlock->blockable_id = $newBlockableId;
        $newBlock->blockable_type = $newBlockableType;
        $newBlock->save();

        foreach ($block->children as $child) {
            $this->duplicateBlock($child, $newWorkout, $newBlock->id);
        }
    }

    public static function getRpeLabel(?int $rpe): string
    {
        return match ($rpe) {
            1, 2 => 'Very Easy',
            3, 4 => 'Easy',
            5, 6 => 'Moderate',
            7, 8 => 'Hard',
            9, 10 => 'Maximum Effort',
            default => '',
        };
    }
}
