<?php

namespace App\Models;

use App\Enums\Workout\ExerciseGroupType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseGroup extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'group_type',
        'rounds',
        'rest_between_rounds_seconds',
    ];

    protected $casts = [
        'group_type' => ExerciseGroupType::class,
        'rounds' => 'integer',
        'rest_between_rounds_seconds' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<WorkoutBlock, $this>
     */
    public function workoutBlock(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(WorkoutBlock::class, 'blockable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ExerciseEntry, $this>
     */
    public function entries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExerciseEntry::class)->orderBy('position');
    }
}
