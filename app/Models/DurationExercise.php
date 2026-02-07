<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class DurationExercise extends Model
{
    /** @use HasFactory<\Database\Factories\DurationExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'target_duration',
        'target_rpe',
    ];

    protected $casts = [
        'target_duration' => 'integer',
        'target_rpe' => 'decimal:1',
    ];

    /**
     * @return MorphOne<BlockExercise, $this>
     */
    public function blockExercise(): MorphOne
    {
        return $this->morphOne(BlockExercise::class, 'exerciseable');
    }
}
