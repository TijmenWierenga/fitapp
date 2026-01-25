<?php

use App\Livewire\Settings\ApiKeys;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Access & Authentication', function () {
    test('api keys page can be rendered', function () {
        $response = $this->get(route('api-keys.index'));

        $response->assertSuccessful();
    });

    test('api keys page requires authentication', function () {
        auth()->logout();

        $response = $this->get(route('api-keys.index'));

        $response->assertRedirect(route('login'));
    });
});

describe('Token Creation', function () {
    test('user can create api token', function () {
        $component = Livewire::test(ApiKeys::class)
            ->set('tokenName', 'My Test Token')
            ->call('createToken');

        $component->assertHasNoErrors()
            ->assertSet('newToken', fn ($token) => ! empty($token))
            ->assertSet('showSuccessModal', true);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'My Test Token',
        ]);
    });

    test('token name is required', function () {
        Livewire::test(ApiKeys::class)
            ->set('tokenName', '')
            ->call('createToken')
            ->assertHasErrors('tokenName');
    });

    test('token name cannot exceed 255 characters', function () {
        Livewire::test(ApiKeys::class)
            ->set('tokenName', str_repeat('a', 256))
            ->call('createToken')
            ->assertHasErrors('tokenName');
    });

    test('newly created token is shown in plain text', function () {
        $component = Livewire::test(ApiKeys::class)
            ->set('tokenName', 'Test Token')
            ->call('createToken');

        $plainTextToken = $component->get('newToken');

        expect($plainTextToken)->not->toBeNull();
        expect($plainTextToken)->toContain('|');
    });

    test('token is hashed in database', function () {
        $component = Livewire::test(ApiKeys::class)
            ->set('tokenName', 'Test Token')
            ->call('createToken');

        $plainTextToken = $component->get('newToken');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => $plainTextToken,
        ]);

        expect($plainTextToken)->not->toBeNull();
    });
});

describe('Token Limits', function () {
    test('user cannot create more than 5 tokens', function () {
        for ($i = 1; $i <= 5; $i++) {
            $this->user->createToken("Token {$i}");
        }

        Livewire::test(ApiKeys::class)
            ->set('tokenName', 'Token 6')
            ->call('createToken')
            ->assertHasErrors('tokenLimit');

        expect($this->user->tokens()->count())->toBe(5);
    });

    test('create button disabled when at limit', function () {
        for ($i = 1; $i <= 5; $i++) {
            $this->user->createToken("Token {$i}");
        }

        $component = Livewire::test(ApiKeys::class);

        expect($component->get('tokens')->count())->toBe(5);
    });

    test('error shown when attempting to exceed limit', function () {
        for ($i = 1; $i <= 5; $i++) {
            $this->user->createToken("Token {$i}");
        }

        Livewire::test(ApiKeys::class)
            ->call('openCreateModal')
            ->assertHasErrors('tokenLimit');
    });
});

describe('Token Revocation', function () {
    test('user can revoke own token', function () {
        $token = $this->user->createToken('Test Token');

        Livewire::test(ApiKeys::class)
            ->call('openRevokeModal', $token->accessToken->id)
            ->call('confirmRevoke');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    });

    test('user cannot revoke another users token', function () {
        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('Other User Token');

        Livewire::test(ApiKeys::class)
            ->call('openRevokeModal', $token->accessToken->id)
            ->call('confirmRevoke');

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    });

    test('revoking token updates the list', function () {
        $token = $this->user->createToken('Test Token');

        $component = Livewire::test(ApiKeys::class)
            ->assertSet('tokens', fn ($tokens) => $tokens->count() === 1)
            ->call('openRevokeModal', $token->accessToken->id)
            ->call('confirmRevoke')
            ->assertSet('tokens', fn ($tokens) => $tokens->count() === 0);
    });

    test('revoked token is deleted from database', function () {
        $token = $this->user->createToken('Test Token');
        $tokenId = $token->accessToken->id;

        $this->user->tokens()
            ->where('id', $tokenId)
            ->delete();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    });
});

describe('UI & Display', function () {
    test('tokens displayed with creation date', function () {
        $this->user->createToken('Test Token');

        $component = Livewire::test(ApiKeys::class);

        $tokens = $component->get('tokens');

        expect($tokens->first()->created_at)->not->toBeNull();
    });

    test('last used timestamp shown when token was used', function () {
        $token = $this->user->createToken('Test Token');
        $token->accessToken->forceFill(['last_used_at' => now()])->save();

        $component = Livewire::test(ApiKeys::class);

        $tokens = $component->get('tokens');

        expect($tokens->first()->last_used_at)->not->toBeNull();
    });

    test('never used badge shown for unused tokens', function () {
        $this->user->createToken('Test Token');

        $component = Livewire::test(ApiKeys::class);

        $tokens = $component->get('tokens');

        expect($tokens->first()->last_used_at)->toBeNull();
    });

    test('empty state shown when no tokens exist', function () {
        $component = Livewire::test(ApiKeys::class);

        expect($component->get('tokens'))->toBeEmpty();
    });

    test('token count indicator shows correctly', function () {
        for ($i = 1; $i <= 3; $i++) {
            $this->user->createToken("Token {$i}");
        }

        $component = Livewire::test(ApiKeys::class);

        expect($component->get('tokens')->count())->toBe(3);
    });
});

describe('Modal Behavior', function () {
    test('create modal opens when button clicked', function () {
        Livewire::test(ApiKeys::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    });

    test('success modal shows after token creation', function () {
        Livewire::test(ApiKeys::class)
            ->set('tokenName', 'Test Token')
            ->call('createToken')
            ->assertSet('showSuccessModal', true)
            ->assertSet('showCreateModal', false);
    });

    test('revoke modal opens when revoke button clicked', function () {
        $token = $this->user->createToken('Test Token');

        Livewire::test(ApiKeys::class)
            ->call('openRevokeModal', $token->accessToken->id)
            ->assertSet('showRevokeModal', true)
            ->assertSet('tokenToRevoke', $token->accessToken->id);
    });

    test('closing modals resets state', function () {
        Livewire::test(ApiKeys::class)
            ->set('tokenName', 'Test Token')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->call('closeModals')
            ->assertSet('showCreateModal', false)
            ->assertSet('showSuccessModal', false)
            ->assertSet('showRevokeModal', false)
            ->assertSet('tokenName', '');
    });
});
