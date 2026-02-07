<?php

namespace App\Models;

use App\Enums\Workout\MuscleGroup;
use Illuminate\Database\Eloquent\Model;

class WorkoutMuscleLoadSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'workout_id',
        'muscle_group',
        'total_load',
        'source_breakdown',
        'completed_at',
    ];

    protected $casts = [
        'muscle_group' => MuscleGroup::class,
        'total_load' => 'float',
        'source_breakdown' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Workout, $this>
     */
    public function workout(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
