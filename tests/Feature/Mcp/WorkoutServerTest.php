<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\PingTool;

it('responds to ping requests', function () {
    $response = WorkoutServer::tool(PingTool::class, []);

    $response
        ->assertOk()
        ->assertSee('pong!')
        ->assertSee('Server time:');
});
