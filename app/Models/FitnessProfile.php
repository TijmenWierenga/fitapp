<?php

namespace App\Models;

use App\Enums\FitnessGoal;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitnessProfile extends Model
{
    /** @use HasFactory<\Database\Factories\FitnessProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'primary_goal',
        'goal_details',
        'available_days_per_week',
        'minutes_per_session',
        'prefer_garmin_exercises',
    ];

    protected $casts = [
        'primary_goal' => FitnessGoal::class,
        'available_days_per_week' => 'integer',
        'minutes_per_session' => 'integer',
        'prefer_garmin_exercises' => 'boolean',
    ];

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function goalDetails(): Attribute
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
