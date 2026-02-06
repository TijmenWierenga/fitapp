<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestBlock extends Model
{
    /** @use HasFactory<\Database\Factories\RestBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'duration_seconds',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<WorkoutBlock, $this>
     */
    public function workoutBlock(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(WorkoutBlock::class, 'blockable');
    }
}
