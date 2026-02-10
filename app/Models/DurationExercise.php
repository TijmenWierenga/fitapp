<?php

namespace App\Models;

use App\Contracts\PresentableExercise;
use App\DataTransferObjects\Workout\ExercisePresentation;
use App\Support\Workout\WorkoutDisplayFormatter as Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class DurationExercise extends Model implements PresentableExercise
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

    public function present(): ExercisePresentation
    {
        $whatLines = [];
        $effortLines = [];

        $duration = Format::duration($this->target_duration);
        if ($duration) {
            $whatLines[] = $duration;
        }

        $rpe = Format::rpe($this->target_rpe);
        if ($rpe) {
            $effortLines[] = $rpe;
        }

        return new ExercisePresentation(
            dotColor: 'bg-emerald-400',
            typeLabel: 'Duration',
            whatLines: $whatLines,
            effortLines: $effortLines,
        );
    }
}
