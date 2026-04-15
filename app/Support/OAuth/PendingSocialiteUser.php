<?php

declare(strict_types=1);

namespace App\Support\OAuth;

use Laravel\Socialite\Contracts\User as SocialiteUserContract;

/**
 * Value object implementing the Socialite User contract,
 * reconstructed from session data during the email collision confirmation flow.
 */
class PendingSocialiteUser implements SocialiteUserContract
{
    private function __construct(
        public readonly string $providerUserId,
        public string $token,
        public ?string $refreshToken,
        public ?int $expiresIn,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $avatar,
    ) {}

    /**
     * @param  array{provider_user_id: string, token: string, refresh_token: ?string, expires_in: ?int, name: ?string, email: ?string, avatar: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            providerUserId: $data['provider_user_id'],
            token: $data['token'],
            refreshToken: $data['refresh_token'] ?? null,
            expiresIn: $data['expires_in'] ?? null,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            avatar: $data['avatar'] ?? null,
        );
    }

    public function getId(): string
    {
        return $this->providerUserId;
    }

    public function getNickname(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }
}
