<?php

namespace App\Models;

use App\Enums\Workout\BlockType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    /** @use HasFactory<\Database\Factories\BlockFactory> */
    use HasFactory;

    protected $fillable = [
        'section_id',
        'block_type',
        'order',
        'rounds',
        'rest_between_exercises',
        'rest_between_rounds',
        'time_cap',
        'work_interval',
        'rest_interval',
        'notes',
    ];

    protected $casts = [
        'block_type' => BlockType::class,
        'order' => 'integer',
        'rounds' => 'integer',
        'rest_between_exercises' => 'integer',
        'rest_between_rounds' => 'integer',
        'time_cap' => 'integer',
        'work_interval' => 'integer',
        'rest_interval' => 'integer',
    ];

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return HasMany<BlockExercise, $this>
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(BlockExercise::class)->orderBy('order');
    }
}
