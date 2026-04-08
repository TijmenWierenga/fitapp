<?php

namespace App\Models;

use App\Enums\FitImportStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitImport extends Model
{
    protected $fillable = [
        'user_id',
        'workout_id',
        'status',
        'raw_data',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FitImportStatus::class,
            'imported_at' => 'datetime',
        ];
    }

    /**
     * Binary columns may return a PHP resource stream on some DB drivers.
     * This accessor ensures raw_data is always returned as a string.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function rawData(): Attribute
    {
        return Attribute::get(function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            if (is_resource($value)) {
                return stream_get_contents($value);
            }

            return (string) $value;
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workout, $this>
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
