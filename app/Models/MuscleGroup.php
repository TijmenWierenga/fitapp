<?php

namespace App\Models;

use App\Enums\BodyPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MuscleGroup extends Model
{
    /** @use HasFactory<\Database\Factories\MuscleGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'body_part',
    ];

    protected $casts = [
        'body_part' => BodyPart::class,
    ];

    /**
     * @return BelongsToMany<Exercise, $this>
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class)
            ->withPivot('load_factor')
            ->withTimestamps();
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForBodyPart(Builder $query, BodyPart $bodyPart): void
    {
        $query->where('body_part', $bodyPart);
    }
}
