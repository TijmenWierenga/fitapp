<?php

use App\Models\User;

test('valid sanctum token authenticates successfully', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Valid Token');

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/mcp/workout-planner', [
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_workouts',
                'arguments' => ['filter' => 'all'],
            ],
        ]);

    $response->assertOk();
});

test('expired token is rejected', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Expired', ['*'], now()->subDay());

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/mcp/workout-planner', [
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_workouts',
                'arguments' => [],
            ],
        ]);

    $response->assertUnauthorized();
});

test('invalid token is rejected', function () {
    $response = $this->withToken('invalid-token-12345')
        ->postJson('/mcp/workout-planner', [
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_workouts',
                'arguments' => [],
            ],
        ]);

    $response->assertUnauthorized();
});
