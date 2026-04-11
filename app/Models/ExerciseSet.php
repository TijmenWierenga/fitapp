<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseSet extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseSetFactory> */
    use HasFactory;

    protected $fillable = [
        'block_exercise_id',
        'set_number',
        'reps',
        'weight',
        'set_duration',
        'distance',
        'duration',
        'avg_heart_rate',
        'max_heart_rate',
        'avg_pace',
        'avg_power',
        'max_power',
        'avg_cadence',
        'total_ascent',
    ];

    protected $casts = [
        'set_number' => 'integer',
        'reps' => 'integer',
        'weight' => 'decimal:2',
        'set_duration' => 'integer',
        'distance' => 'decimal:2',
        'duration' => 'integer',
        'avg_heart_rate' => 'integer',
        'max_heart_rate' => 'integer',
        'avg_pace' => 'integer',
        'avg_power' => 'integer',
        'max_power' => 'integer',
        'avg_cadence' => 'integer',
        'total_ascent' => 'integer',
    ];

    /**
     * @return BelongsTo<BlockExercise, $this>
     */
    public function blockExercise(): BelongsTo
    {
        return $this->belongsTo(BlockExercise::class);
    }
}
