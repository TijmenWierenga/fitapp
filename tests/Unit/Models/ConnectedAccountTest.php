<?php

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('belongs to a user', function () {
    $account = ConnectedAccount::factory()->create();

    expect($account->user)->toBeInstanceOf(User::class);
});

it('encrypts access token', function () {
    $account = ConnectedAccount::factory()->create(['access_token' => 'my-secret-token']);

    $rawValue = DB::table('connected_accounts')->where('id', $account->id)->value('access_token');

    expect($rawValue)->not->toBe('my-secret-token');
    expect($account->fresh()->access_token)->toBe('my-secret-token');
});

it('casts scopes to array', function () {
    $account = ConnectedAccount::factory()->create(['scopes' => ['read', 'write']]);

    expect($account->fresh()->scopes)->toBe(['read', 'write']);
});
