<?php

namespace App\Models;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Injury extends Model
{
    /** @use HasFactory<\Database\Factories\InjuryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'injury_type',
        'body_part',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'injury_type' => InjuryType::class,
        'body_part' => BodyPart::class,
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

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
     * @return Attribute<bool, never>
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->ended_at === null,
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<$this>  $query
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<$this>  $query
     */
    public function scopeResolved(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNotNull('ended_at');
    }
}
