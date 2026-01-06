<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUpcoming(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('completed_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
    }

    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc');
    }

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
        return self::create([
            'user_id' => $this->user_id,
            'name' => $this->name,
            'scheduled_at' => $scheduledAt,
        ]);
    }
}
