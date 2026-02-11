<?php

namespace App\Models;

use App\Enums\InjuryReportType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InjuryReport extends Model
{
    /** @use HasFactory<\Database\Factories\InjuryReportFactory> */
    use HasFactory;

    protected $fillable = [
        'injury_id',
        'user_id',
        'type',
        'content',
        'reported_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => InjuryReportType::class,
            'reported_at' => 'date',
        ];
    }

    /**
     * @return Attribute<string, string>
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            set: function (string $value): string {
                $trimmed = trim($value);

                return $trimmed === '' ? $value : $trimmed;
            },
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Injury, $this>
     */
    public function injury(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Injury::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
