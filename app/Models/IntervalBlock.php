<?php

namespace App\Models;

use App\Enums\Workout\IntervalIntensity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntervalBlock extends Model
{
    /** @use HasFactory<\Database\Factories\IntervalBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'duration_seconds',
        'distance_meters',
        'target_pace_seconds_per_km',
        'target_heart_rate_zone',
        'intensity',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'distance_meters' => 'integer',
        'target_pace_seconds_per_km' => 'integer',
        'target_heart_rate_zone' => 'integer',
        'intensity' => IntervalIntensity::class,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<WorkoutBlock, $this>
     */
    public function workoutBlock(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(WorkoutBlock::class, 'blockable');
    }
}
