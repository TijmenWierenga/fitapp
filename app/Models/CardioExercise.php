<?php

namespace App\Models;

use App\Contracts\PresentableExercise;
use App\DataTransferObjects\Workout\ExercisePresentation;
use App\Support\Workout\WorkoutDisplayFormatter as Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CardioExercise extends Model implements PresentableExercise
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

    public function present(): ExercisePresentation
    {
        $whatLines = [];
        $effortLines = [];

        $duration = Format::duration($this->target_duration);
        if ($duration) {
            $whatLines[] = $duration;
        }

        $distance = Format::distance($this->target_distance);
        if ($distance) {
            $whatLines[] = $distance;
        }

        $pace = Format::paceRange($this->target_pace_min, $this->target_pace_max);
        if ($pace) {
            $effortLines[] = "Pace: {$pace}";
        }

        $hrZone = Format::hrZone($this->target_heart_rate_zone);
        if ($hrZone) {
            $effortLines[] = $hrZone;
        }

        $hrRange = Format::hrRange($this->target_heart_rate_min, $this->target_heart_rate_max);
        if ($hrRange) {
            $effortLines[] = $hrRange;
        }

        $power = Format::power($this->target_power);
        if ($power) {
            $effortLines[] = $power;
        }

        return new ExercisePresentation(
            dotColor: 'bg-blue-400',
            typeLabel: 'Cardio',
            whatLines: $whatLines,
            effortLines: $effortLines,
        );
    }
}
