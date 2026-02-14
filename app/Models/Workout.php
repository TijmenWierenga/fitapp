<?php

namespace App\Models;

use App\Enums\Workout\Activity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class Workout extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Workout $workout): void {
            $exerciseables = BlockExercise::query()
                ->whereIn('block_id', Block::query()
                    ->select('id')
                    ->whereIn('section_id', $workout->sections()->select('id')))
                ->select('exerciseable_type', 'exerciseable_id')
                ->get()
                ->groupBy('exerciseable_type');

            foreach ($exerciseables as $type => $records) {
                $model = Relation::getMorphedModel($type) ?? $type;
                $model::whereIn('id', $records->pluck('exerciseable_id'))->delete();
            }
        });
    }

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
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
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
        if ($this->isCompleted()) {
            return false;
        }

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

        $this->load('sections.blocks.exercises.exerciseable');

        foreach ($this->sections as $section) {
            $newSection = $newWorkout->sections()->create([
                'name' => $section->name,
                'order' => $section->order,
                'notes' => $section->notes,
            ]);

            foreach ($section->blocks as $block) {
                $newBlock = $newSection->blocks()->create([
                    'block_type' => $block->block_type,
                    'order' => $block->order,
                    'rounds' => $block->rounds,
                    'rest_between_exercises' => $block->rest_between_exercises,
                    'rest_between_rounds' => $block->rest_between_rounds,
                    'time_cap' => $block->time_cap,
                    'work_interval' => $block->work_interval,
                    'rest_interval' => $block->rest_interval,
                    'notes' => $block->notes,
                ]);

                foreach ($block->exercises as $exercise) {
                    $newExerciseable = $exercise->exerciseable->replicate();
                    $newExerciseable->save();

                    $newBlock->exercises()->create([
                        'name' => $exercise->name,
                        'order' => $exercise->order,
                        'exerciseable_type' => $exercise->exerciseable_type,
                        'exerciseable_id' => $newExerciseable->id,
                        'notes' => $exercise->notes,
                    ]);
                }
            }
        }

        return $newWorkout;
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
