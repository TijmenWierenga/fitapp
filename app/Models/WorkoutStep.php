<?php

namespace App\Models;

use App\Enums\DurationType;
use App\Enums\Intensity;
use App\Enums\StepType;
use App\Enums\TargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutStep extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutStepFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'parent_id',
        'order',
        'type',
        'intensity',
        'duration_type',
        'duration_value',
        'target_type',
        'target_value_low',
        'target_value_high',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => StepType::class,
            'intensity' => Intensity::class,
            'duration_type' => DurationType::class,
            'target_type' => TargetType::class,
            'order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Workout, $this>
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * @return BelongsTo<WorkoutStep, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkoutStep::class, 'parent_id');
    }

    /**
     * @return HasMany<WorkoutStep, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(WorkoutStep::class, 'parent_id')->orderBy('order');
    }

    public function summary(): string
    {
        if ($this->type === StepType::Repetition) {
            return str($this->duration_value)->append('x repeats')->toString();
        }

        $summary = match ($this->duration_type) {
            DurationType::Time => $this->formatTime(),
            DurationType::Distance => str($this->duration_value / 1000)->append('km'),
            DurationType::Open => str('Open'),
            DurationType::Calories => str($this->duration_value)->append(' cal'),
            DurationType::HrLessThan => str('< ')->append($this->duration_value, ' bpm'),
            DurationType::HrGreaterThan => str('> ')->append($this->duration_value, ' bpm'),
            default => str($this->duration_value ?? ''),
        };

        return $summary
            ->when($this->intensity, fn ($s) => $s->append(' ', str($this->intensity->value)->ucfirst()))
            ->trim()
            ->toString();
    }

    protected function formatTime(): \Illuminate\Support\Stringable
    {
        $minutes = floor($this->duration_value / 60);
        $seconds = $this->duration_value % 60;

        return str('')
            ->when($minutes > 0, fn ($s) => $s->append($minutes, 'm'))
            ->when($seconds > 0, fn ($s) => $s->append($minutes > 0 ? ' ' : '', $seconds, 's'));
    }
}
