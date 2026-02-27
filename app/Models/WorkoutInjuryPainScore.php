<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutInjuryPainScore extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutInjuryPainScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'injury_id',
        'pain_score',
    ];

    protected $casts = [
        'pain_score' => 'integer',
    ];

    /**
     * @return BelongsTo<Workout, $this>
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * @return BelongsTo<Injury, $this>
     */
    public function injury(): BelongsTo
    {
        return $this->belongsTo(Injury::class);
    }
}
