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
    public string $token;

    public ?string $refreshToken;

    public ?int $expiresIn;

    /**
     * @param  array{provider_user_id: string, token: string, refresh_token: ?string, expires_in: ?int, name: ?string, email: ?string, avatar: ?string}  $data
     */
    public function __construct(private readonly array $data)
    {
        $this->token = $data['token'];
        $this->refreshToken = $data['refresh_token'] ?? null;
        $this->expiresIn = $data['expires_in'] ?? null;
    }

    public function getId(): string
    {
        return $this->data['provider_user_id'];
    }

    public function getNickname(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->data['email'] ?? null;
    }

    public function getAvatar(): ?string
    {
        return $this->data['avatar'] ?? null;
    }
}
