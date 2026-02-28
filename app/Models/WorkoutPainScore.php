<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutPainScore extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutPainScoreFactory> */
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

    public static function getPainLabel(int $score): string
    {
        return match (true) {
            $score === 0 => 'No Pain',
            $score <= 3 => 'Mild',
            $score <= 6 => 'Moderate',
            default => 'Severe',
        };
    }
}
