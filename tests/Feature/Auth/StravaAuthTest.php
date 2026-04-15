<?php

use App\Enums\OAuthProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function mockSocialiteRedirect(): void
{
    Socialite::shouldReceive('driver')
        ->with('strava')
        ->andReturn(Mockery::mock(\Laravel\Socialite\Contracts\Provider::class, function ($mock) {
            $mock->shouldReceive('scopes')->with(['read'])->andReturnSelf();
            $mock->shouldReceive('stateless')->andReturnSelf();
            $mock->shouldReceive('with')->andReturnSelf();
            $mock->shouldReceive('redirect')->andReturn(redirect('https://www.strava.com/oauth/authorize'));
        }));
}

function mockSocialiteCallback(Mockery\MockInterface $socialiteUser): void
{
    Socialite::shouldReceive('driver')
        ->with('strava')
        ->andReturn(Mockery::mock(\Laravel\Socialite\Contracts\Provider::class, function ($mock) use ($socialiteUser) {
            $mock->shouldReceive('stateless')->andReturnSelf();
            $mock->shouldReceive('user')->andReturn($socialiteUser);
        }));
}

function makeSocialiteUser(array $overrides = []): Mockery\MockInterface
{
    $defaults = [
        'id' => '12345',
        'name' => 'John Doe',
        'nickname' => 'johndoe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
        'token' => 'access-token',
        'refreshToken' => 'refresh-token',
        'expiresIn' => 21600,
    ];

    $data = array_merge($defaults, $overrides);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->token = $data['token'];
    $socialiteUser->refreshToken = $data['refreshToken'];
    $socialiteUser->expiresIn = $data['expiresIn'];
    $socialiteUser->shouldReceive('getId')->andReturn($data['id']);
    $socialiteUser->shouldReceive('getName')->andReturn($data['name']);
    $socialiteUser->shouldReceive('getNickname')->andReturn($data['nickname']);
    $socialiteUser->shouldReceive('getEmail')->andReturn($data['email']);
    $socialiteUser->shouldReceive('getAvatar')->andReturn($data['avatar']);

    return $socialiteUser;
}

function encryptedState(string $intent = 'login', ?int $userId = null): string
{
    return Crypt::encryptString(json_encode([
        'intent' => $intent,
        'user_id' => $userId,
        'expires_at' => now()->addMinutes(10)->timestamp,
    ]));
}

it('redirects to Strava OAuth page', function () {
    mockSocialiteRedirect();

    $response = $this->get(route('auth.strava.redirect'));

    $response->assertRedirect('https://www.strava.com/oauth/authorize');
});

it('creates a new user when no matching account exists', function () {
    $socialiteUser = makeSocialiteUser();
    mockSocialiteCallback($socialiteUser);

    $state = encryptedState('login');

    $response = $this->get(route('auth.strava.callback', ['state' => $state]));

    $response->assertRedirect(route('onboarding'));

    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('John Doe')
        ->and($user->password)->toBeNull();

    $account = ConnectedAccount::where('provider_user_id', '12345')->first();
    expect($account)->not->toBeNull()
        ->and($account->user_id)->toBe($user->id)
        ->and($account->provider)->toBe(OAuthProvider::Strava->value);
});

it('logs in existing user when Strava account is already linked', function () {
    $user = User::factory()->create();
    ConnectedAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => OAuthProvider::Strava->value,
        'provider_user_id' => '12345',
    ]);

    $socialiteUser = makeSocialiteUser(['email' => $user->email]);
    mockSocialiteCallback($socialiteUser);

    $state = encryptedState('login');

    $response = $this->get(route('auth.strava.callback', ['state' => $state]));

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('redirects to confirm-link when email collision occurs', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $socialiteUser = makeSocialiteUser();
    mockSocialiteCallback($socialiteUser);

    $state = encryptedState('login');

    $response = $this->get(route('auth.strava.callback', ['state' => $state]));

    $response->assertRedirect(route('auth.strava.confirm-link'));
    $response->assertSessionHas('strava_link_pending');
});

it('links Strava for authenticated user with link intent', function () {
    $user = User::factory()->create();

    $socialiteUser = makeSocialiteUser(['id' => '99999', 'email' => $user->email]);
    mockSocialiteCallback($socialiteUser);

    $state = encryptedState('link', $user->id);

    $response = $this->get(route('auth.strava.callback', ['state' => $state]));

    $response->assertRedirect(route('connected-accounts.edit'));

    expect($user->connectedAccounts()->where('provider', OAuthProvider::Strava->value)->exists())->toBeTrue();
});
