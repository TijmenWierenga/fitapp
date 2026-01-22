<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Workout, $this>
     */
    public function workouts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Get the user's timezone as a DateTimeZone object.
     * Falls back to UTC if no timezone is set.
     */
    public function getTimezoneObject(): DateTimeZone
    {
        return new DateTimeZone($this->timezone ?? 'UTC');
    }

    /**
     * Convert a Carbon instance to the user's timezone.
     */
    public function toUserTimezone(Carbon $date): Carbon
    {
        return $date->copy()->setTimezone($this->getTimezoneObject());
    }

    /**
     * Get the current time in the user's timezone.
     */
    public function currentTimeInTimezone(): Carbon
    {
        return Carbon::now($this->getTimezoneObject());
    }
}
