<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteBlock extends Model
{
    /** @use HasFactory<\Database\Factories\NoteBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'content',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<WorkoutBlock, $this>
     */
    public function workoutBlock(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(WorkoutBlock::class, 'blockable');
    }
}
