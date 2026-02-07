<?php

namespace App\Models;

use App\Enums\Workout\Equipment;
use App\Enums\Workout\ExerciseCategory;
use App\Enums\Workout\MovementPattern;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'equipment',
        'movement_pattern',
        'primary_muscles',
        'secondary_muscles',
    ];

    protected $casts = [
        'category' => ExerciseCategory::class,
        'equipment' => Equipment::class,
        'movement_pattern' => MovementPattern::class,
        'primary_muscles' => 'array',
        'secondary_muscles' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ExerciseMuscleLoad, $this>
     */
    public function muscleLoads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExerciseMuscleLoad::class);
    }
}
