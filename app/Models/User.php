<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Authorizable, HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

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
        'role',
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
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<FitnessProfile, $this>
     */
    public function fitnessProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FitnessProfile::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Injury, $this>
     */
    public function injuries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Injury::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Injury, $this>
     */
    public function activeInjuries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->injuries()->active();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<InjuryReport, $this>
     */
    public function injuryReports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InjuryReport::class);
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
     * Convert a CarbonImmutable instance to the user's timezone.
     */
    public function toUserTimezone(CarbonImmutable $date): CarbonImmutable
    {
        return $date->setTimezone($this->getTimezoneObject());
    }

    /**
     * Get the current time in the user's timezone.
     */
    public function currentTimeInTimezone(): CarbonImmutable
    {
        return CarbonImmutable::now($this->getTimezoneObject());
    }
}
