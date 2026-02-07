<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StrengthExercise extends Model
{
    /** @use HasFactory<\Database\Factories\StrengthExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'target_sets',
        'target_reps_min',
        'target_reps_max',
        'target_weight',
        'target_rpe',
        'target_tempo',
        'rest_after',
    ];

    protected $casts = [
        'target_sets' => 'integer',
        'target_reps_min' => 'integer',
        'target_reps_max' => 'integer',
        'target_weight' => 'decimal:2',
        'target_rpe' => 'decimal:1',
        'rest_after' => 'integer',
    ];

    /**
     * @return MorphOne<BlockExercise, $this>
     */
    public function blockExercise(): MorphOne
    {
        return $this->morphOne(BlockExercise::class, 'exerciseable');
    }
}
