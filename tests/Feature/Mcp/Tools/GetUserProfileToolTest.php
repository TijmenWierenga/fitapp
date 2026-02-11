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
