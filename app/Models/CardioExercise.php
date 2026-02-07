<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CardioExercise extends Model
{
    /** @use HasFactory<\Database\Factories\CardioExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'target_duration',
        'target_distance',
        'target_pace_min',
        'target_pace_max',
        'target_heart_rate_zone',
        'target_heart_rate_min',
        'target_heart_rate_max',
        'target_power',
    ];

    protected $casts = [
        'target_duration' => 'integer',
        'target_distance' => 'decimal:2',
        'target_pace_min' => 'integer',
        'target_pace_max' => 'integer',
        'target_heart_rate_zone' => 'integer',
        'target_heart_rate_min' => 'integer',
        'target_heart_rate_max' => 'integer',
        'target_power' => 'integer',
    ];

    /**
     * @return MorphOne<BlockExercise, $this>
     */
    public function blockExercise(): MorphOne
    {
        return $this->morphOne(BlockExercise::class, 'exerciseable');
    }
}
