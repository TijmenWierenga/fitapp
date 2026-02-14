<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Exercise|null $exercise
 */
class BlockExercise extends Model
{
    /** @use HasFactory<\Database\Factories\BlockExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'block_id',
        'exercise_id',
        'name',
        'order',
        'exerciseable_type',
        'exerciseable_id',
        'notes',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * @return BelongsTo<Block, $this>
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function exerciseable(): MorphTo
    {
        return $this->morphTo();
    }
}
