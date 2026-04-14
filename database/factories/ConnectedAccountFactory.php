<?php

namespace Database\Factories;

use App\Enums\OAuthProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConnectedAccount>
 */
class ConnectedAccountFactory extends Factory
{
    protected $model = ConnectedAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => OAuthProvider::Strava->value,
            'provider_user_id' => (string) fake()->unique()->randomNumber(8),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'avatar' => fake()->imageUrl(),
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHours(6),
            'scopes' => ['read'],
        ];
    }

    public function strava(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => OAuthProvider::Strava->value,
        ]);
    }
}
