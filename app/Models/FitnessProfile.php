<?php

namespace App\Models;

use App\Enums\BiologicalSex;
use App\Enums\ExperienceLevel;
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
        'experience_level',
        'date_of_birth',
        'biological_sex',
        'body_weight_kg',
        'height_cm',
        'has_gym_access',
        'home_equipment',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'primary_goal' => FitnessGoal::class,
            'available_days_per_week' => 'integer',
            'minutes_per_session' => 'integer',
            'prefer_garmin_exercises' => 'boolean',
            'experience_level' => ExperienceLevel::class,
            'date_of_birth' => 'date',
            'biological_sex' => BiologicalSex::class,
            'body_weight_kg' => 'decimal:2',
            'height_cm' => 'integer',
            'has_gym_access' => 'boolean',
            'home_equipment' => 'array',
        ];
    }

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
     * @return Attribute<int|null, never>
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->date_of_birth?->age,
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
