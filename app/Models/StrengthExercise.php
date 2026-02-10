<?php

namespace App\Models;

use App\Contracts\PresentableExercise;
use App\DataTransferObjects\Workout\ExercisePresentation;
use App\Support\Workout\WorkoutDisplayFormatter as Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StrengthExercise extends Model implements PresentableExercise
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

    public function present(): ExercisePresentation
    {
        $whatLines = [];
        $effortLines = [];
        $restLines = [];

        $setsReps = Format::setsReps($this->target_sets, $this->target_reps_min, $this->target_reps_max);
        if ($setsReps) {
            $whatLines[] = $setsReps;
        }

        $weight = Format::weight($this->target_weight);
        if ($weight) {
            $whatLines[] = "at {$weight}";
        }

        if ($this->target_tempo) {
            $whatLines[] = "tempo {$this->target_tempo}";
        }

        $rpe = Format::rpe($this->target_rpe);
        if ($rpe) {
            $effortLines[] = $rpe;
        }

        $rest = Format::rest($this->rest_after);
        if ($rest) {
            $restLines[] = "{$rest} between sets";
        }

        return new ExercisePresentation(
            dotColor: 'bg-orange-400',
            typeLabel: 'Strength',
            whatLines: $whatLines,
            effortLines: $effortLines,
            restLines: $restLines,
        );
    }
}
