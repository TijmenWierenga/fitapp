<?php

namespace App\Models;

use App\Enums\Workout\BlockType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutBlock extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'parent_id',
        'type',
        'position',
        'label',
        'repeat_count',
        'rest_between_repeats_seconds',
        'blockable_type',
        'blockable_id',
    ];

    protected $casts = [
        'type' => BlockType::class,
        'position' => 'integer',
        'repeat_count' => 'integer',
        'rest_between_repeats_seconds' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Workout, $this>
     */
    public function workout(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<self, $this>
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<self, $this>
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<self, $this>
     */
    public function nestedChildren(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->children()->with('nestedChildren', 'blockable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<Model, $this>
     */
    public function blockable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calculate nesting depth (1-based). Root blocks are depth 1.
     */
    public function depth(): int
    {
        return $this->parent ? $this->parent->depth() + 1 : 1;
    }

    /**
     * Maximum allowed nesting depth.
     */
    public static function maxDepth(): int
    {
        return 3;
    }
}
