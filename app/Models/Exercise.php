<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'force',
        'level',
        'mechanic',
        'equipment',
        'category',
        'instructions',
        'aliases',
        'description',
        'tips',
    ];

    protected $casts = [
        'instructions' => 'array',
        'aliases' => 'array',
        'tips' => 'array',
    ];

    /**
     * @return BelongsToMany<MuscleGroup, $this>
     */
    public function muscleGroups(): BelongsToMany
    {
        return $this->belongsToMany(MuscleGroup::class)
            ->withPivot('load_factor')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<MuscleGroup, $this>
     */
    public function primaryMuscles(): BelongsToMany
    {
        return $this->muscleGroups()->wherePivot('load_factor', 1.0);
    }

    /**
     * @return BelongsToMany<MuscleGroup, $this>
     */
    public function secondaryMuscles(): BelongsToMany
    {
        return $this->muscleGroups()->wherePivot('load_factor', 0.5);
    }

    /**
     * @return HasMany<BlockExercise, $this>
     */
    public function blockExercises(): HasMany
    {
        return $this->hasMany(BlockExercise::class);
    }
}
