<?php

namespace App\Models;

use App\Enums\Workout\MuscleGroup;
use App\Enums\Workout\MuscleRole;
use Illuminate\Database\Eloquent\Model;

class ExerciseMuscleLoad extends Model
{
    protected $fillable = [
        'exercise_id',
        'muscle_group',
        'role',
        'load_factor',
    ];

    protected $casts = [
        'muscle_group' => MuscleGroup::class,
        'role' => MuscleRole::class,
        'load_factor' => 'float',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Exercise, $this>
     */
    public function exercise(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
