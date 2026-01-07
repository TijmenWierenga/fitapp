<?php

namespace App\Models;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Step extends Model
{
    /** @use HasFactory<\Database\Factories\StepFactory> */
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
            'step_kind' => StepKind::class,
            'intensity' => Intensity::class,
            'duration_type' => DurationType::class,
            'target_type' => TargetType::class,
            'target_mode' => TargetMode::class,
            'skip_last_recovery' => 'boolean',
            'repeat_count' => 'integer',
            'sort_order' => 'integer',
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
     * @return BelongsTo<Step, $this>
     */
    public function parentStep(): BelongsTo
    {
        return $this->belongsTo(Step::class, 'parent_step_id');
    }

    /**
     * @return HasMany<Step, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Step::class, 'parent_step_id')->orderBy('sort_order');
    }
}
