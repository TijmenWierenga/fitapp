<?php

namespace App\Models;

use App\Enums\Workout\Activity;
use App\Enums\Workout\MuscleGroup;
use App\Enums\Workout\MuscleRole;
use Illuminate\Database\Eloquent\Model;

class ActivityMuscleLoad extends Model
{
    protected $fillable = [
        'activity',
        'muscle_group',
        'role',
        'load_factor',
    ];

    protected $casts = [
        'activity' => Activity::class,
        'muscle_group' => MuscleGroup::class,
        'role' => MuscleRole::class,
        'load_factor' => 'float',
    ];
}
