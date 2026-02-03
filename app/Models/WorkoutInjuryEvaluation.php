<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $workout_id
 * @property int $injury_id
 * @property int|null $discomfort_score
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Workout $workout
 * @property-read Injury $injury
 */
class WorkoutInjuryEvaluation extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutInjuryEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'injury_id',
        'discomfort_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discomfort_score' => 'integer',
        ];
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function notes(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $trimmed = trim($value);

                return $trimmed === '' ? null : $trimmed;
            },
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Workout, $this>
     */
    public function workout(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Injury, $this>
     */
    public function injury(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Injury::class);
    }
}
