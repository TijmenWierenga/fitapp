<?php

namespace App\Models;

use App\Enums\Fit\GarminExerciseCategory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Exercise extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseFactory> */
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'slug',
        'force',
        'level',
        'mechanic',
        'equipment',
        'category',
        'instructions',
        'aliases',
        'description',
        'tips',
        'garmin_exercise_category',
        'garmin_exercise_name',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'instructions' => 'array',
        'aliases' => 'array',
        'tips' => 'array',
        'garmin_exercise_category' => GarminExerciseCategory::class,
    ];

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'equipment' => $this->equipment,
            'level' => $this->level,
        ];
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasGarminMapping(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->garmin_exercise_category !== null,
        );
    }

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
