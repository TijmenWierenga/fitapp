<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetUserProfileTool;
use App\Models\User;

it('returns user profile information', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetUserProfileTool::class);

    $response->assertOk()
        ->assertSee('John Doe')
        ->assertSee('john@example.com')
        ->assertSee('Europe/Amsterdam')
        ->assertSee('JD');
});

it('returns each user their own profile data', function () {
    $alice = User::factory()->withTimezone('UTC')->create([
        'name' => 'Alice Smith',
        'email' => 'alice@example.com',
    ]);
    $bob = User::factory()->withTimezone('America/New_York')->create([
        'name' => 'Bob Jones',
        'email' => 'bob@example.com',
    ]);

    $aliceResponse = WorkoutServer::actingAs($alice)->tool(GetUserProfileTool::class);
    $bobResponse = WorkoutServer::actingAs($bob)->tool(GetUserProfileTool::class);

    $aliceResponse->assertOk()
        ->assertSee('Alice Smith')
        ->assertSee('alice@example.com')
        ->assertDontSee('Bob Jones')
        ->assertDontSee('bob@example.com');

    $bobResponse->assertOk()
        ->assertSee('Bob Jones')
        ->assertSee('bob@example.com')
        ->assertDontSee('Alice Smith')
        ->assertDontSee('alice@example.com');
});
