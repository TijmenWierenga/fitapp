<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseEntry extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'exercise_group_id',
        'exercise_id',
        'position',
        'sets',
        'reps',
        'duration_seconds',
        'weight_kg',
        'rpe_target',
        'rest_between_sets_seconds',
        'notes',
    ];

    protected $casts = [
        'position' => 'integer',
        'sets' => 'integer',
        'reps' => 'integer',
        'duration_seconds' => 'integer',
        'weight_kg' => 'float',
        'rpe_target' => 'integer',
        'rest_between_sets_seconds' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ExerciseGroup, $this>
     */
    public function exerciseGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExerciseGroup::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Exercise, $this>
     */
    public function exercise(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
